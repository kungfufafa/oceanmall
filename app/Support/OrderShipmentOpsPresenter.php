<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OrderShipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;

/**
 * Shared shipment/inventory payloads for cpanel Komerce fulfillment UI.
 */
final class OrderShipmentOpsPresenter
{
    /**
     * @return list<array<string, mixed>>
     */
    public function shipments(Order $order): array
    {
        return OrderShipment::query()
            ->with(['lines.purchasable', 'inventory'])
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get()
            ->map(function (OrderShipment $shipment): array {
                $deliveryOrderNo = $this->deliveryOrderNo($shipment);
                $canPrint = $deliveryOrderNo !== null;
                $canOverride = in_array($shipment->status, ['pending', 'ready'], true)
                    && ! filled($shipment->awb)
                    && ! filled($shipment->tracking_number);

                return [
                    'id' => $shipment->id,
                    'inventory_id' => $shipment->inventory_id,
                    'inventory_name' => $shipment->inventory?->name,
                    'status' => $shipment->status,
                    'status_label' => $this->statusLabel(is_string($shipment->status) ? $shipment->status : null),
                    'awb' => $shipment->awb,
                    'tracking_number' => $shipment->tracking_number,
                    'carrier' => $shipment->carrier_name ?? $shipment->carrier_code,
                    'service' => $shipment->service_name ?? $shipment->service_code,
                    'cost' => (int) $shipment->cost,
                    'currency' => $shipment->currency_code,
                    'delivery_order_no' => $deliveryOrderNo,
                    'can_print_label' => $canPrint,
                    'print_hint' => $canPrint
                        ? null
                        : 'Label unlocks after the RajaOngkir delivery order is created (usually right after payment clears).',
                    'can_override' => $canOverride,
                    'lines' => $shipment->lines->map(static function ($line): array {
                        $purchasable = $line->purchasable;

                        return [
                            'id' => $line->id,
                            'name' => $purchasable instanceof Model
                                ? (string) ($purchasable->getAttribute('name') ?? class_basename($purchasable))
                                : 'Item',
                            'purchasable_type' => $line->purchasable_type,
                            'purchasable_id' => $line->purchasable_id,
                            'qty' => (int) $line->qty,
                        ];
                    })->values()->all(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inventories(): array
    {
        return Inventory::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'rajaongkir_origin_id'])
            ->map(static fn (Inventory $inventory): array => [
                'id' => $inventory->id,
                'name' => $inventory->name,
                'is_default' => (bool) $inventory->is_default,
                'rajaongkir_origin_id' => $inventory->rajaongkir_origin_id,
                'ready_for_shipping' => filled($inventory->rajaongkir_origin_id),
            ])
            ->values()
            ->all();
    }

    /**
     * @param  list<array<string, mixed>>  $shipments
     */
    public function printableCount(array $shipments): int
    {
        return Collection::make($shipments)->where('can_print_label', true)->count();
    }

    public function deliveryOrderNo(OrderShipment $shipment): ?string
    {
        $orderNo = data_get($shipment->metadata, 'komerce.order_no');

        return is_scalar($orderNo) && trim((string) $orderNo) !== ''
            ? trim((string) $orderNo)
            : null;
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending', 'ready' => 'Waiting for label',
            'labeled' => 'Labeled',
            'picked_up' => 'Picked up',
            'in_transit' => 'In transit',
            'delivered' => 'Delivered',
            default => $status ? str_replace('_', ' ', ucfirst($status)) : 'Unknown',
        };
    }
}
