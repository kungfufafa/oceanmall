<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Support\OrderShipmentOpsPresenter;
use Shopper\Core\Models\Order;
use Throwable;

/**
 * Cancel the Komerce delivery order that matches a local shipment.
 */
final readonly class CancelKomerceDelivery
{
    public function __construct(
        private ShippingDeliveryClient $delivery,
        private OrderShipmentOpsPresenter $presenter,
    ) {}

    public function handle(Order $order): void
    {
        if (! komerce_shipping_delivery_enabled()) {
            return;
        }

        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->get();

        foreach ($shipments as $shipment) {
            $this->cancelShipment($shipment);
        }
    }

    public function cancelShipment(OrderShipment $shipment): void
    {
        $orderNo = $this->presenter->deliveryOrderNo($shipment);

        if ($orderNo === null) {
            return;
        }

        if (data_get($shipment->metadata, 'komerce.cancelled_at')) {
            return;
        }

        try {
            $this->delivery->cancelOrder($orderNo);
        } catch (Throwable $e) {
            report($e);
        }

        $metadata = is_array($shipment->metadata) ? $shipment->metadata : [];
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['cancelled_at'] = now()->toIso8601String();
        $metadata['komerce'] = $komerce;

        $shipment->forceFill([
            'status' => NormalizeShipmentStatus::CANCELLED,
            'metadata' => $metadata,
        ])->save();
    }
}
