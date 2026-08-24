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
        private RefreshShipmentTracking $refreshTracking,
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

        $airwayBill = $cnote ?: $shipment->awb ?: $shipment->tracking_number;
        if (! is_scalar($airwayBill) || trim((string) $airwayBill) === '') {
            return 'ignored';
        }

        // Set only in memory first. RefreshShipmentTracking persists it only
        // after the authenticated provider lookup succeeds.
        $shipment->setAttribute('awb', trim((string) $airwayBill));
        $shipment->setAttribute('tracking_number', trim((string) $airwayBill));
        $shipment = $this->refreshTracking->handle($shipment);

        $metadata = is_array($shipment->metadata) ? $shipment->metadata : [];
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['webhook_reported_status'] = $status;
        $komerce['webhook_received_at'] = now()->toIso8601String();
        $komerce['order_no'] = $orderNo;
        $metadata['komerce'] = $komerce;

        $shipment->forceFill(['metadata' => $metadata])->save();

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
