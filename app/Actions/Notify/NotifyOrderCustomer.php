<?php

declare(strict_types=1);

namespace App\Actions\Notify;

use App\Enums\OrderNotificationType;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Shopper\Core\Models\Order;

final class NotifyOrderCustomer
{
    public function handle(Order $order, OrderNotificationType $type): void
    {
        $customer = $order->customer;

        if (! $customer instanceof User) {
            return;
        }

        $customer->notify(new OrderStatusNotification($order, $type));
    }
}
