<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\OrderShipment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;

final class OrderShowController extends Controller
{
    public function __invoke(Order $order): Response
    {
        Gate::authorize('override-allocation', $order);

        $order->loadMissing(['items', 'customer', 'shippingAddress']);

        $shipments = OrderShipment::query()
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

        $inventories = Inventory::query()
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

        $printableCount = collect($shipments)->where('can_print_label', true)->count();

        return Inertia::render('admin/order-show', [
            'order' => [
                'id' => $order->id,
                'number' => (string) $order->number,
                'status' => $order->status?->value ?? $order->status,
                'payment_status' => $order->payment_status?->value ?? $order->payment_status,
                'shipping_status' => $order->shipping_status?->value ?? $order->shipping_status,
                'price_amount' => (int) $order->price_amount,
                'currency_code' => $order->currency_code,
                'customer_email' => $order->customer?->email,
                'customer_name' => trim(implode(' ', array_filter([
                    $order->customer?->first_name,
                    $order->customer?->last_name,
                ]))) ?: null,
            ],
            'shipments' => $shipments,
            'inventories' => $inventories,
            'komerceEnabled' => komerce_enabled(),
            'canPrintAnyLabel' => $printableCount > 0,
            'printableShipmentCount' => $printableCount,
        ]);
    }

    private function deliveryOrderNo(OrderShipment $shipment): ?string
    {
        $orderNo = data_get($shipment->metadata, 'komerce.order_no');

        return is_scalar($orderNo) && trim((string) $orderNo) !== ''
            ? trim((string) $orderNo)
            : null;
    }

    private function statusLabel(?string $status): string
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
