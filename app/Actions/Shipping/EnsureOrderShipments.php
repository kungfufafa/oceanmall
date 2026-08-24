<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Shipping\RajaOngkirCourier;
use Illuminate\Support\Collection;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;

/**
 * Guarantee warehouse shipment rows exist so RajaOngkir can issue an AWB.
 * Shopper does not generate tracking here; it only holds the allocation.
 */
final class EnsureOrderShipments
{
    /**
     * @return Collection<int, OrderShipment>
     */
    public function handle(Order $order): Collection
    {
        $existing = OrderShipment::query()->where('order_id', $order->id)->get();

        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $defaultInventory = Inventory::query()->where('is_default', true)->first()
            ?? Inventory::query()->whereNotNull('rajaongkir_origin_id')->where('rajaongkir_origin_id', '!=', '')->first()
            ?? Inventory::query()->first();

        if (! $defaultInventory) {
            return collect();
        }

        $order->loadMissing(['items', 'shippingOption.carrier']);

        $shippingOption = $order->shippingOption;
        $carrierCode = 'jne';
        $serviceCode = 'REG';
        $serviceName = 'REG';
        $shippingCost = (int) ($order->shipping_total ?? 0);

        if ($shippingOption) {
            $carrierCode = strtolower((string) ($shippingOption->carrier?->slug ?: $carrierCode));
            $serviceName = (string) ($shippingOption->name ?: $serviceName);
            $serviceCode = strtoupper((string) (data_get($shippingOption->metadata, 'service_code') ?: $serviceName));
            if ($shippingCost === 0 && isset($shippingOption->price)) {
                $shippingCost = (int) $shippingOption->price;
            }
        }

        $metadata = is_array($order->metadata)
            ? $order->metadata
            : json_decode((string) $order->metadata, true) ?? [];

        if (isset($metadata['shipping']) && is_array($metadata['shipping'])) {
            $shippingMeta = $metadata['shipping'];
            $carrierCode = strtolower((string) ($shippingMeta['courier_code'] ?? $shippingMeta['courier'] ?? $carrierCode));
            $serviceCode = (string) ($shippingMeta['service_code'] ?? $shippingMeta['service'] ?? $serviceCode);
            $serviceName = (string) ($shippingMeta['service_name'] ?? $shippingMeta['service_description'] ?? $serviceName);
            if (! empty($shippingMeta['cost'])) {
                $shippingCost = (int) $shippingMeta['cost'];
            }
        }

        $carrierCode = strtolower(trim($carrierCode));
        $serviceName = RajaOngkirCourier::deliveryService($serviceName !== '' ? $serviceName : $serviceCode);
        $carrierName = RajaOngkirCourier::deliveryName($carrierCode);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $defaultInventory->id,
            'status' => 'pending',
            'carrier_code' => $carrierCode,
            'carrier_name' => $carrierName,
            'service_code' => $serviceCode,
            'service_name' => $serviceName,
            'cost' => $shippingCost,
            'currency_code' => $order->currency_code ?? 'IDR',
            'metadata' => [
                'rate' => [
                    'carrier_code' => $carrierCode,
                    'carrier_name' => $carrierName,
                    'service_code' => $serviceCode,
                    'service_name' => $serviceName,
                    'shipping_name' => $carrierName,
                    'shipping_cost' => $shippingCost,
                    'amount' => $shippingCost,
                ],
            ],
        ]);

        foreach ($order->items as $item) {
            $shipment->lines()->create([
                'purchasable_type' => $item->product_type,
                'purchasable_id' => $item->product_id,
                'qty' => max(1, (int) $item->quantity),
            ]);
        }

        return collect([$shipment]);
    }
}
