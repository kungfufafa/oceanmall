<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Adapters;

use App\Domain\Shipping\Contracts\ShippingDriverContract;
use App\Domain\Shipping\DTO\DeliveryOrderRequestData;
use App\Domain\Shipping\DTO\DeliveryOrderResultData;
use App\Domain\Shipping\DTO\ShippingRateData;
use App\Domain\Shipping\DTO\ShippingRateRequestData;
use App\Domain\Shipping\DTO\ShippingTrackingData;
use App\Services\Komerce\ShippingCostClient;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Support\Collection;

class RajaOngkirShippingAdapter implements ShippingDriverContract
{
    public function __construct(
        protected ShippingCostClient $costClient,
        protected ShippingDeliveryClient $deliveryClient,
    ) {}

    public function calculateRates(ShippingRateRequestData $request): Collection
    {
        $response = $this->costClient->calculate(
            origin: ['id' => $request->originId],
            destination: ['id' => $request->destinationSubdistrictId],
            weightGrams: max(1, $request->weightInGrams),
            couriers: $request->couriers,
        );

        $results = collect();
        $costs = data_get($response, 'data', data_get($response, 'results', []));

        if (! is_array($costs)) {
            return $results;
        }

        foreach ($costs as $courierItem) {
            if (! is_array($courierItem)) {
                continue;
            }

            $courierCode = (string) data_get($courierItem, 'code', data_get($courierItem, 'courier'));
            $courierName = (string) data_get($courierItem, 'name', strtoupper($courierCode));
            $services = data_get($courierItem, 'costs', data_get($courierItem, 'services', []));

            if (! is_array($services)) {
                continue;
            }

            foreach ($services as $serviceItem) {
                if (! is_array($serviceItem)) {
                    continue;
                }

                $serviceCode = (string) data_get($serviceItem, 'service');
                $serviceName = (string) data_get($serviceItem, 'description', $serviceCode);
                $costDetails = data_get($serviceItem, 'cost', []);
                $costVal = 0;
                $etd = null;

                if (is_array($costDetails) && isset($costDetails[0]) && is_array($costDetails[0])) {
                    $costVal = (int) data_get($costDetails[0], 'value', 0);
                    $etd = (string) data_get($costDetails[0], 'etd', '');
                } else {
                    $costVal = (int) data_get($serviceItem, 'cost', 0);
                    $etd = (string) data_get($serviceItem, 'etd', '');
                }

                $results->push(new ShippingRateData(
                    courierCode: strtolower($courierCode),
                    courierName: $courierName,
                    serviceCode: strtoupper($serviceCode),
                    serviceName: $serviceName,
                    cost: $costVal,
                    etdDays: $etd ?: null,
                    description: $serviceName,
                ));
            }
        }

        return $results;
    }

    public function createDeliveryOrder(DeliveryOrderRequestData $request): DeliveryOrderResultData
    {
        $payload = [
            'order_id' => $request->orderNumber,
            'shipper_name' => $request->senderName,
            'shipper_phone' => $request->senderPhone,
            'shipper_destination_id' => (string) $request->originId,
            'receiver_name' => $request->receiverName,
            'receiver_phone' => $request->receiverPhone,
            'receiver_destination_id' => (string) $request->destinationSubdistrictId,
            'receiver_address' => $request->receiverAddress,
            'courier' => strtolower($request->courier),
            'service' => strtoupper($request->service),
            'weight' => max(1, $request->weightInGrams),
            'items' => $request->items,
        ];

        $response = $this->deliveryClient->createPickupOrder($payload);

        $dataObj = data_get($response, 'data', []);

        return new DeliveryOrderResultData(
            deliveryId: (string) data_get($dataObj, 'order_id', data_get($dataObj, 'id', $request->orderNumber)),
            waybillNumber: (string) data_get($dataObj, 'airway_bill', data_get($dataObj, 'waybill_number', data_get($dataObj, 'tracking_number', ''))),
            status: (string) data_get($dataObj, 'status', 'manifested'),
            courierCode: strtolower($request->courier),
            serviceCode: strtoupper($request->service),
            rawResponse: $response,
        );
    }

    public function trackShipment(string $waybill, string $courier): ShippingTrackingData
    {
        $response = $this->deliveryClient->trackWaybill($waybill, $courier);
        $dataObj = data_get($response, 'data', []);

        $status = strtolower((string) data_get($dataObj, 'status', 'on_delivery'));
        $mappedStatus = match ($status) {
            'delivered', 'completed' => 'delivered',
            'manifested', 'pickup', 'on_delivery', 'shipping' => 'shipped',
            default => 'pending',
        };

        $history = data_get($dataObj, 'history', data_get($dataObj, 'manifest', []));

        return new ShippingTrackingData(
            waybillNumber: $waybill,
            courierCode: strtolower($courier),
            status: $mappedStatus,
            history: is_array($history) ? $history : [],
            deliveredAt: (string) data_get($dataObj, 'delivered_at'),
            rawResponse: $response,
        );
    }
}
