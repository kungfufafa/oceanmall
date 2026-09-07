<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Support\OrderShipmentOpsPresenter;
use RuntimeException;
use Shopper\Core\Models\Order;

/**
 * Copy Komerce Shipping Delivery detail onto the local shipment.
 * Komerce is the source of truth for order_no, AWB, and courier status.
 */
final readonly class ReconcileKomerceShipment
{
    public function __construct(
        private ShippingDeliveryClient $delivery,
        private OrderShipmentOpsPresenter $presenter,
        private NormalizeShipmentStatus $normalizeStatus,
        private SyncOrderShippingFromShipments $syncOrderShipping,
    ) {}

    public function handle(OrderShipment $shipment): OrderShipment
    {
        if (! komerce_shipping_delivery_enabled()) {
            return $shipment;
        }

        $orderNo = $this->presenter->deliveryOrderNo($shipment);

        if ($orderNo === null) {
            return $shipment;
        }

        $detail = $this->delivery->detailOrder($orderNo);
        $status = data_get($detail, 'meta.status');

        if (is_scalar($status) && strtolower(trim((string) $status)) !== 'success') {
            $message = data_get($detail, 'meta.message');

            throw new RuntimeException(sprintf(
                'Komerce detail-order failed for [%s]: %s.',
                $orderNo,
                is_scalar($message) ? (string) $message : 'unknown provider error',
            ));
        }

        $awb = $this->scalar($detail, [
            'data.awb',
            'data.airway_bill',
            'data.cnote',
        ]);
        $remoteOrderNo = $this->scalar($detail, ['data.order_no']) ?? $orderNo;
        $remoteOrderId = $this->scalar($detail, ['data.order_id']);
        $rawStatus = $this->scalar($detail, [
            'data.last_status',
            'data.status',
            'data.order_status',
        ]);
        $normalized = $this->normalizeStatus->handle(
            $rawStatus,
            is_string($shipment->status) ? $shipment->status : null,
        );

        $metadata = is_array($shipment->metadata) ? $shipment->metadata : [];
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['order_no'] = $remoteOrderNo;
        $komerce['detail'] = is_array(data_get($detail, 'data')) ? data_get($detail, 'data') : $detail;
        $komerce['reconciled_at'] = now()->toIso8601String();

        if ($remoteOrderId !== null) {
            $komerce['order_id'] = $remoteOrderId;
        }

        if ($awb !== null) {
            $komerce['awb'] = $awb;
            $komerce['tracking_number'] = $awb;
        }

        if ($rawStatus !== null) {
            $komerce['tracking_status'] = $rawStatus;
        }

        $attributes = ['metadata' => array_merge($metadata, ['komerce' => $komerce])];

        if ($awb !== null) {
            $attributes['awb'] = $awb;
            $attributes['tracking_number'] = $awb;
        }

        if ($normalized !== null && $this->shouldWriteStatus($shipment->status, $normalized)) {
            $attributes['status'] = $normalized;
        }

        $shipment->forceFill($attributes)->save();

        $order = $shipment->order()->first();
        if ($order instanceof Order) {
            $this->syncOrderShipping->handle($order);
        }

        return $shipment->refresh();
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  list<string>  $paths
     */
    private function scalar(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = data_get($payload, $path);
            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function shouldWriteStatus(mixed $current, string $incoming): bool
    {
        $current = is_string($current) ? strtolower(trim($current)) : '';

        if ($incoming === NormalizeShipmentStatus::CANCELLED) {
            return true;
        }

        if ($current === NormalizeShipmentStatus::CANCELLED) {
            return false;
        }

        return $this->rank($incoming) >= $this->rank($current);
    }

    private function rank(string $status): int
    {
        return match ($status) {
            NormalizeShipmentStatus::PENDING, 'ready' => 0,
            NormalizeShipmentStatus::LABELED => 1,
            NormalizeShipmentStatus::PICKED_UP => 2,
            NormalizeShipmentStatus::IN_TRANSIT => 3,
            NormalizeShipmentStatus::DELIVERED => 4,
            NormalizeShipmentStatus::CANCELLED => 5,
            default => 0,
        };
    }
}
