<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Actions\Notify\NotifyOrderCustomer;
use App\Enums\OrderNotificationType;
use App\Models\OrderShipment;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;

final class SyncOrderShippingFromShipments
{
    public function __construct(private readonly NotifyOrderCustomer $notifyOrderCustomer) {}

    public function handle(Order $order): Order
    {
        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->get();

        if ($shipments->isEmpty()) {
            return $order;
        }

        foreach ($shipments as $shipment) {
            $this->syncShopperOrderShipping($order, $shipment);
        }

        $statuses = $shipments->pluck('status')
            ->map(static fn (mixed $status): string => strtolower(trim((string) $status)))
            ->filter()
            ->values()
            ->all();

        if ($statuses === []) {
            return $order;
        }

        $previousShipping = $order->shipping_status;
        $shippingStatus = $this->aggregateShippingStatus($statuses);
        $updates = ['shipping_status' => $shippingStatus];

        if (
            $shippingStatus === ShippingStatus::Delivered
            && in_array($order->status, [OrderStatus::New, OrderStatus::Processing], true)
        ) {
            $updates['status'] = OrderStatus::Completed;
        }

        $order->forceFill($updates)->save();
        $order = $order->refresh();

        if ($previousShipping !== $shippingStatus) {
            if (in_array($shippingStatus, [ShippingStatus::Shipped, ShippingStatus::PartiallyShipped], true)) {
                $this->notifyOrderCustomer->handle($order, OrderNotificationType::Shipped);
            }

            if (in_array($shippingStatus, [ShippingStatus::Delivered, ShippingStatus::PartiallyDelivered], true)) {
                $this->notifyOrderCustomer->handle($order, OrderNotificationType::Delivered);
            }
        }

        return $order;
    }

    /**
     * @param  list<string>  $statuses
     */
    private function aggregateShippingStatus(array $statuses): ShippingStatus
    {
        $total = count($statuses);
        $delivered = count(array_filter(
            $statuses,
            static fn (string $status): bool => $status === NormalizeShipmentStatus::DELIVERED,
        ));
        $active = count(array_filter(
            $statuses,
            static fn (string $status): bool => in_array($status, [
                NormalizeShipmentStatus::LABELED,
                NormalizeShipmentStatus::PICKED_UP,
                NormalizeShipmentStatus::IN_TRANSIT,
                NormalizeShipmentStatus::DELIVERED,
            ], true),
        ));

        if ($delivered === $total) {
            return ShippingStatus::Delivered;
        }

        if ($delivered > 0) {
            return ShippingStatus::PartiallyDelivered;
        }

        if ($active === $total && $active > 0) {
            return ShippingStatus::Shipped;
        }

        if ($active > 0) {
            return ShippingStatus::PartiallyShipped;
        }

        return ShippingStatus::Unfulfilled;
    }

    private function syncShopperOrderShipping(Order $order, OrderShipment $shipment): void
    {
        $awb = $shipment->awb ?? $shipment->tracking_number;
        if (! $awb) {
            return;
        }

        $carrierCode = strtolower((string) ($shipment->carrier_code ?: $shipment->carrier_name));
        $carrierId = \Shopper\Core\Models\Carrier::query()
            ->where('slug', $carrierCode)
            ->orWhere('name', 'like', "%{$carrierCode}%")
            ->value('id') ?? \Shopper\Core\Models\Carrier::query()->where('is_enabled', true)->value('id');

        $status = match (strtolower((string) $shipment->status)) {
            'delivered' => \Shopper\Core\Enum\ShipmentStatus::Delivered,
            'picked_up', 'in_transit', 'labeled' => \Shopper\Core\Enum\ShipmentStatus::InTransit,
            'failed' => \Shopper\Core\Enum\ShipmentStatus::DeliveryFailed,
            default => \Shopper\Core\Enum\ShipmentStatus::Pending,
        };

        \Shopper\Core\Models\OrderShipping::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'tracking_number' => $awb,
            ],
            [
                'carrier_id' => $carrierId,
                'status' => $status,
                'tracking_url' => url("/account/orders/{$order->id}/track"),
                'shipped_at' => now(),
            ],
        );
    }
}
