<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Adapters;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Domain\Shipping\Contracts\ShippingDriverContract;
use App\Domain\Shipping\DTO\DeliveryOrderRequestData;
use App\Domain\Shipping\DTO\DeliveryOrderResultData;
use App\Domain\Shipping\DTO\ShippingRateData;
use App\Domain\Shipping\DTO\ShippingRateRequestData;
use App\Domain\Shipping\DTO\ShippingTrackingData;
use App\Services\Komerce\ShippingCostClient;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

class RajaOngkirShippingAdapter implements ShippingDriverContract
{
    public function __construct(
        protected ShippingCostClient $costClient,
        protected ShippingDeliveryClient $deliveryClient,
    ) {}

    public function calculateRates(ShippingRateRequestData $request): Collection
    {
        if ($request->weightInGrams <= 0) {
            throw new InvalidArgumentException('Shipping Cost API weight must be a positive gram value.');
        }

        if ($request->couriers === []) {
            throw new InvalidArgumentException('At least one courier must be selected explicitly.');
        }

        $response = $this->costClient->calculate(
            origin: ['id' => $request->originId],
            destination: ['id' => $request->destinationSubdistrictId],
            weightGrams: $request->weightInGrams,
            couriers: $request->couriers,
        );

        $results = collect();
        $costs = data_get($response, 'data', data_get($response, 'results', []));

        if (! is_array($costs)) {
            return $results;
        }

        foreach ($costs as $rate) {
            if (! is_array($rate)) {
                continue;
            }

            // Shipping Cost API V2 returns one flat row per service.
            $courierCode = trim((string) data_get($rate, 'code'));
            $courierName = trim((string) data_get($rate, 'name'));
            $serviceCode = trim((string) data_get($rate, 'service'));
            $description = trim((string) data_get($rate, 'description'));
            $cost = data_get($rate, 'cost');
            $etd = trim((string) data_get($rate, 'etd'));

            if ($courierCode === '' || $serviceCode === '' || ! is_numeric($cost)) {
                continue;
            }

            $results->push(new ShippingRateData(
                courierCode: strtolower($courierCode),
                courierName: $courierName !== '' ? $courierName : strtoupper($courierCode),
                serviceCode: strtoupper($serviceCode),
                serviceName: $description !== '' ? $description : $serviceCode,
                cost: (int) $cost,
                etdDays: $etd !== '' ? $etd : null,
                description: $description !== '' ? $description : null,
            ));
        }

        return $results;
    }

    public function createDeliveryOrder(DeliveryOrderRequestData $request): DeliveryOrderResultData
    {
        throw new LogicException(
            'DeliveryOrderRequestData cannot represent the official Store Order plus Pickup contracts. Use the fulfillment workflow with a validated Shipping Delivery rate instead.',
        );
    }

    public function trackShipment(string $waybill, string $courier): ShippingTrackingData
    {
        $response = $this->deliveryClient->track($waybill, $courier);
        $dataObj = data_get($response, 'data', []);
        $rawStatus = trim((string) data_get($dataObj, 'last_status'));
        $mappedStatus = (new NormalizeShipmentStatus)->handle($rawStatus, NormalizeShipmentStatus::PENDING)
            ?? NormalizeShipmentStatus::PENDING;
        $history = data_get($dataObj, 'history', []);

        return new ShippingTrackingData(
            waybillNumber: $waybill,
            courierCode: strtolower($courier),
            status: $mappedStatus,
            history: is_array($history) ? $history : [],
            deliveredAt: null,
            rawResponse: $response,
        );
    }
}
