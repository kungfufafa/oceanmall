<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Support\ShipmentTrackingRefreshThrottle;
use Shopper\Core\Models\Order;
use Throwable;

/**
 * Vue account order-show, API GET order, and Shopper Komerce panel.
 * When a shipment already has AWB/tracking_number, refresh via
 * RefreshShipmentTracking (Delivery history-airway-bill). Provider
 * errors are swallowed the same way ApplyDeliveryWebhookStatus acks
 * without dropping AWB.
 */
final class RefreshShipmentTrackingOnView
{
    public function __construct(
        private readonly RefreshShipmentTracking $refreshTracking,
    ) {}

    public function handle(Order $order): Order
    {
        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get()
            ->filter(static fn (OrderShipment $shipment): bool => filled($shipment->awb) || filled($shipment->tracking_number));

        foreach ($shipments as $shipment) {
            if (! ShipmentTrackingRefreshThrottle::claim((int) $shipment->id)) {
                continue;
            }

            try {
                $this->refreshTracking->handle($shipment);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return $order->refresh();
    }
}
