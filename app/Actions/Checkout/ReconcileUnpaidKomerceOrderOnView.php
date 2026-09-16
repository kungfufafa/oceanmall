<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Throwable;

/**
 * Vue checkout-success, Vue order-show, Shopper order panel, and Expo poll
 * GET every 10s. When the payment webhook cannot reach this host, that read
 * must still reconcile a captured Komerce payment so the living flow can
 * continue to AWB.
 */
final class ReconcileUnpaidKomerceOrderOnView
{
    public function __construct(
        private readonly SyncKomercePaymentStatus $syncPayment,
    ) {}

    public function handle(Order $order): Order
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return $order;
        }

        if (! komerce_payment_enabled() && ! qrisly_enabled()) {
            return $order;
        }

        try {
            $this->syncPayment->handle($order);
        } catch (Throwable $e) {
            report($e);
        }

        return $order->refresh();
    }
}
