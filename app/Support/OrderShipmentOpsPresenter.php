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
            ->with(['lines.purchasable', 'inventory', 'order.shippingAddress', 'order.customer'])
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get()
            ->map(function (OrderShipment $shipment) use ($order): array {
                $deliveryOrderNo = $this->deliveryOrderNo($shipment);
                $canPrint = $this->canPrintLabel($shipment);
                $needsPickup = $this->needsPickup($shipment);
                $canOverride = ! $this->isLockedForOverride($shipment);

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
                    'can_print_label' => $canPrint,
                    'needs_pickup' => $needsPickup,
                    'print_hint' => $canPrint
                        ? null
                        : ($needsPickup
                            ? 'Request pickup dulu. Stiker resi baru bisa dicetak setelah pickup Komerce berhasil.'
                            : 'Daftarkan paket ke Komerce dulu, lalu request pickup. Setelah nomor resi terbit, stiker bisa dicetak.'),
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

    public function isLockedForOverride(OrderShipment $shipment): bool
    {
        if (! in_array($shipment->status, ['pending', 'ready'], true)) {
            return true;
        }

        if ($this->filled($shipment->awb) || $this->filled($shipment->tracking_number)) {
            return true;
        }

        return $this->deliveryOrderNo($shipment) !== null;
    }

    public function needsPickup(OrderShipment $shipment): bool
    {
        if ($this->filled($shipment->awb) || $this->filled($shipment->tracking_number)) {
            return false;
        }

        return $this->deliveryOrderNo($shipment) !== null;
    }

    public function canPrintLabel(OrderShipment $shipment): bool
    {
        if ($this->deliveryOrderNo($shipment) === null) {
            return false;
        }

        if ($this->filled($shipment->awb) || $this->filled($shipment->tracking_number)) {
            return true;
        }

        return is_array(data_get($shipment->metadata, 'komerce.pickup_response'));
    }

    public function statusLabel(?string $status): string
    {
        return match ($status) {
            'pending', 'ready' => 'Menunggu resi',
            'labeled' => 'Resi terbit',
            'picked_up' => 'Sudah di-pickup',
            'in_transit' => 'Dalam pengiriman',
            'delivered' => 'Terkirim',
            'cancelled' => 'Dibatalkan di Komerce',
            default => $status ? str_replace('_', ' ', ucfirst($status)) : 'Tidak diketahui',
        };
    }

    private function filled(mixed $value): bool
    {
        return is_scalar($value) && trim((string) $value) !== '';
    }
}
