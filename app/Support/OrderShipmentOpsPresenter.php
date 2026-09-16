<?php

declare(strict_types=1);

namespace App\Support;

use App\Actions\Account\CancelOrderByCustomer;
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
            ->with(['lines.purchasable', 'inventory', 'order.shippingAddress', 'order.customer'])
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get()
            ->map(function (OrderShipment $shipment) use ($order): array {
                $deliveryOrderNo = $this->deliveryOrderNo($shipment);
                $canPrint = $deliveryOrderNo !== null;
                $canOverride = in_array($shipment->status, ['pending', 'ready'], true)
                    && ! filled($shipment->awb)
                    && ! filled($shipment->tracking_number);

                $inventory = $shipment->inventory;
                $shipperAddress = implode(', ', array_filter([
                    $inventory?->street_address,
                    $inventory?->city,
                    $inventory?->postal_code,
                ], static fn (mixed $p): bool => is_scalar($p) && trim((string) $p) !== ''));

                $shippingAddress = $order->shippingAddress;
                $receiverName = trim(implode(' ', array_filter([
                    $shippingAddress?->first_name,
                    $shippingAddress?->last_name,
                ], static fn (mixed $p): bool => is_scalar($p) && trim((string) $p) !== '')));
                if ($receiverName === '') {
                    $receiverName = (string) ($order->customer?->full_name ?? 'Pelanggan');
                }

                $receiverAddress = implode(', ', array_filter([
                    $shippingAddress?->street_address,
                    $shippingAddress?->street_address_plus,
                    $shippingAddress?->city,
                    $shippingAddress?->postal_code,
                ], static fn (mixed $p): bool => is_scalar($p) && trim((string) $p) !== ''));

                $receiverPhone = (string) ($shippingAddress?->phone_number ?? $shippingAddress?->phone ?? '-');

                $carrier = (string) ($shipment->carrier_name ?: $shipment->carrier_code);
                $service = (string) ($shipment->service_name ?: $shipment->service_code);

                if (str_contains($service, ':')) {
                    $parts = explode(':', $service);
                    $service = end($parts);
                }

                if ($carrier === $service || in_array(strtoupper($carrier), ['STD', 'EZ', 'REG', 'EXPRESS', 'SIUNT'], true)) {
                    $carrier = strtoupper((string) ($shipment->carrier_code ?: $carrier));
                }

                return [
                    'id' => $shipment->id,
                    'inventory_id' => $shipment->inventory_id,
                    'inventory_name' => $shipment->inventory?->name,
                    'status' => $shipment->status,
                    'status_label' => $this->statusLabel(is_string($shipment->status) ? $shipment->status : null),
                    'awb' => $shipment->awb,
                    'tracking_number' => $shipment->tracking_number,
                    'carrier' => $carrier,
                    'service' => strtoupper($service),
                    'carrier_logo' => KomerceCourierAssets::logoUrl($shipment->carrier_code),
                    'cost' => (int) $shipment->cost,
                    'currency' => $shipment->currency_code,
                    'delivery_order_no' => $deliveryOrderNo,
                    'fulfillment_error' => is_string(data_get($shipment->metadata, 'komerce.fulfillment_error'))
                        ? (string) data_get($shipment->metadata, 'komerce.fulfillment_error')
                        : null,
                    'tracking_history' => ShipmentTrackingHistory::fromShipment($shipment),
                    'origin_pin_ready' => $this->inventoryHasPinPoint($inventory),
                    'destination_pin_ready' => $this->orderHasDestinationPin($order),
                    'can_print_label' => $canPrint,
                    'print_hint' => $canPrint
                        ? null
                        : 'Label unlocks after the RajaOngkir delivery order is created (usually right after payment clears).',
                    'can_override' => $canOverride,
                    'shipper_name' => $inventory?->name ?? 'Gudang Utama',
                    'shipper_address' => $shipperAddress ?: 'Jl. Tuparev No. 109F, Cirebon',
                    'shipper_phone' => (string) ($inventory?->phone_number ?? '-'),
                    'receiver_name' => $receiverName,
                    'receiver_address' => $receiverAddress ?: '-',
                    'receiver_phone' => $receiverPhone,
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
     * Rejected/mismatched payment callback marker written by
     * MarkOrderPaidFromKomerce (metadata.komerce.payment_alert), if any.
     *
     * @return array{reason: string, payment_id: ?string, expected_amount: ?int, remote_amount: ?int, occurred_at: ?string}|null
     */
    public function paymentAlert(Order $order): ?array
    {
        $metadata = $order->getAttribute('metadata');

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $alert = data_get($metadata, 'komerce.payment_alert');

        if (! is_array($alert) || ! is_string($alert['reason'] ?? null)) {
            return null;
        }

        return [
            'reason' => (string) $alert['reason'],
            'payment_id' => is_scalar($alert['payment_id'] ?? null) ? (string) $alert['payment_id'] : null,
            'expected_amount' => is_numeric($alert['expected_amount'] ?? null) ? (int) $alert['expected_amount'] : null,
            'remote_amount' => is_numeric($alert['remote_amount'] ?? null) ? (int) $alert['remote_amount'] : null,
            'occurred_at' => is_string($alert['occurred_at'] ?? null) ? $alert['occurred_at'] : null,
        ];
    }

    public function cancelledReasonLabel(Order $order): ?string
    {
        return CancelOrderByCustomer::cancelledReasonLabel($order, 'admin');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function inventories(): array
    {
        return Inventory::query()
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name', 'is_default', 'rajaongkir_origin_id', 'latitude', 'longitude'])
            ->map(fn (Inventory $inventory): array => [
                'id' => $inventory->id,
                'name' => $inventory->name,
                'is_default' => (bool) $inventory->is_default,
                'rajaongkir_origin_id' => $inventory->rajaongkir_origin_id,
                'has_pin_point' => $this->inventoryHasPinPoint($inventory),
                'ready_for_shipping' => filled($inventory->rajaongkir_origin_id)
                    && $this->inventoryHasPinPoint($inventory),
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
        return ShipmentStatusLabel::for($status);
    }

    private function inventoryHasPinPoint(?Inventory $inventory): bool
    {
        return $inventory !== null
            && is_numeric($inventory->getAttribute('latitude'))
            && is_numeric($inventory->getAttribute('longitude'));
    }

    private function orderHasDestinationPin(Order $order): bool
    {
        $metadata = $order->getAttribute('metadata');

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $address = data_get($metadata, 'shipping_address', []);
        if (! is_array($address)) {
            $address = [];
        }

        foreach ([$address, $metadata] as $source) {
            $pin = $source['rajaongkir_pin_point'] ?? $source['pin_point'] ?? null;
            if (is_string($pin) && str_contains($pin, ',')) {
                return true;
            }

            if (is_numeric($source['latitude'] ?? null) && is_numeric($source['longitude'] ?? null)) {
                return true;
            }
        }

        $shippingAddress = $order->shippingAddress;
        $lat = $shippingAddress?->getAttribute('latitude');
        $lng = $shippingAddress?->getAttribute('longitude');

        return is_numeric($lat) && is_numeric($lng);
    }
}
