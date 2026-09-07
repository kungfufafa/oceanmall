<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Shipping\CancelKomerceDelivery;
use Shopper\Core\Events\Orders\OrderCancelled;
use Shopper\Core\Models\Order;

final class CancelKomerceDeliveryOnOrderCancelled
{
    public function handle(OrderCancelled $event): void
    {
        $order = $event->order instanceof Order
            ? $event->order
            : Order::query()->find($event->order->id);

        if (! $order instanceof Order) {
            return;
        }

        resolve(CancelKomerceDelivery::class)->handle($order);
    }
}
