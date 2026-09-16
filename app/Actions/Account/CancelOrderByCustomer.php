<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\Checkout\CancelUnpaidKomerceOrder;
use Illuminate\Validation\ValidationException;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final readonly class CancelOrderByCustomer
{
    public const REASON = 'Cancelled by customer';

    public function __construct(private CancelUnpaidKomerceOrder $cancelUnpaidOrder) {}

    public function handle(Order $order): Order
    {
        if ($order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'cancel' => __('Pesanan sudah dibatalkan.'),
            ]);
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'cancel' => __('Pesanan sudah dibayar dan tidak bisa dibatalkan.'),
            ]);
        }

        if ($order->payment_status !== PaymentStatus::Pending || $order->status !== OrderStatus::New) {
            throw ValidationException::withMessages([
                'cancel' => __('Pesanan tidak bisa dibatalkan pada status saat ini.'),
            ]);
        }

        return $this->cancelUnpaidOrder->handle($order, self::REASON);
    }

    public static function isCancellable(Order $order): bool
    {
        return $order->status === OrderStatus::New
            && $order->payment_status === PaymentStatus::Pending;
    }
}
