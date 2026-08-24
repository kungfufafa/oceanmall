<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Shipping\DispatchRajaOngkirDelivery;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class OrderObserver
{
    public function updated(Order $order): void
    {
        if (app()->runningUnitTests()) {
            return;
        }

        $paid = $order->payment_status === PaymentStatus::Paid;
        $wasPaidChanged = $order->wasChanged('payment_status');

        if ($paid && $wasPaidChanged) {
            resolve(DispatchRajaOngkirDelivery::class)->handle($order);
        }
    }
}
