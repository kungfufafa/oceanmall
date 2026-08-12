<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class OrderObserver
{
    public function updated(Order $order): void
    {
        if (! komerce_shipping_delivery_enabled() || app()->runningUnitTests()) {
            return;
        }

        $paid = $order->payment_status === PaymentStatus::Paid;
        $wasPaidChanged = $order->wasChanged('payment_status');

        if ($paid && $wasPaidChanged) {
            $this->dispatchPendingDeliveries($order);
        }
    }

    private function dispatchPendingDeliveries(Order $order): void
    {
        OrderShipment::query()
            ->where('order_id', $order->id)
            ->whereNull('awb')
            ->whereNull('tracking_number')
            ->pluck('id')
            ->each(static function (mixed $shipmentId): void {
                CreateRajaOngkirDeliveryForShipment::dispatch((int) $shipmentId);
            });
    }
}
