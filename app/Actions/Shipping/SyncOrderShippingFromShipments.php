<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Models\OrderShipment;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;

final class SyncOrderShippingFromShipments
{
    public function handle(Order $order): Order
    {
        $statuses = OrderShipment::query()
            ->where('order_id', $order->id)
            ->pluck('status')
            ->map(static fn (mixed $status): string => strtolower(trim((string) $status)))
            ->filter()
            ->values()
            ->all();

        if ($statuses === []) {
            return $order;
        }

        $shippingStatus = $this->aggregateShippingStatus($statuses);
        $updates = ['shipping_status' => $shippingStatus];

        if (
            $shippingStatus === ShippingStatus::Delivered
            && in_array($order->status, [OrderStatus::New, OrderStatus::Processing], true)
        ) {
            $updates['status'] = OrderStatus::Completed;
        }

        $order->forceFill($updates)->save();

        return $order->refresh();
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
}
