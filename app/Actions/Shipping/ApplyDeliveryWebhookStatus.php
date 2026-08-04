<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;

/**
 * Apply a Komerce Shipping Delivery webhook payload
 * ({order_no, cnote, status}) onto the matching shipment.
 */
final readonly class ApplyDeliveryWebhookStatus
{
    public function __construct(
        private NormalizeShipmentStatus $normalizeStatus,
        private SyncOrderShippingFromShipments $syncOrderShipping,
    ) {}

    /**
     * @return 'handled'|'not_found'|'ignored'
     */
    public function handle(string $orderNo, ?string $cnote, ?string $status): string
    {
        $orderNo = trim($orderNo);
        $cnote = $cnote !== null ? trim($cnote) : null;
        $status = $status !== null ? trim($status) : null;

        if ($orderNo === '' && ($cnote === null || $cnote === '')) {
            return 'ignored';
        }

        $shipment = $this->findShipment($orderNo, $cnote);

        if ($shipment === null) {
            return 'not_found';
        }

        $metadata = is_array($shipment->metadata) ? $shipment->metadata : [];
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['webhook_status'] = $status;
        $komerce['webhook_at'] = now()->toIso8601String();

        if ($cnote !== null && $cnote !== '') {
            $komerce['awb'] = $cnote;
        }

        if ($orderNo !== '') {
            $komerce['order_no'] = $orderNo;
        }

        $metadata['komerce'] = $komerce;

        $attributes = ['metadata' => $metadata];

        if ($cnote !== null && $cnote !== '') {
            $attributes['awb'] = $cnote;
            $attributes['tracking_number'] = $cnote;
        }

        $normalized = $this->normalizeStatus->handle($status, is_string($shipment->status) ? $shipment->status : null);

        if ($normalized !== null) {
            $attributes['status'] = $normalized;
        }

        $shipment->forceFill($attributes)->save();

        $order = $shipment->order()->first();

        if ($order !== null) {
            $this->syncOrderShipping->handle($order);
        }

        return 'handled';
    }

    private function findShipment(string $orderNo, ?string $cnote): ?OrderShipment
    {
        if ($orderNo !== '') {
            $byOrderNo = OrderShipment::query()
                ->where('metadata->komerce->order_no', $orderNo)
                ->orderByDesc('id')
                ->first();

            if ($byOrderNo !== null) {
                return $byOrderNo;
            }
        }

        if ($cnote !== null && $cnote !== '') {
            return OrderShipment::query()
                ->where(function ($query) use ($cnote): void {
                    $query->where('awb', $cnote)
                        ->orWhere('tracking_number', $cnote);
                })
                ->orderByDesc('id')
                ->first();
        }

        return null;
    }
}
