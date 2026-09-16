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

    /**
     * Reason recorded by CancelUnpaidKomerceOrder (metadata.komerce.cancelled_reason),
     * for cancelled orders only.
     */
    public static function cancelledReason(Order $order): ?string
    {
        if ($order->status !== OrderStatus::Cancelled) {
            return null;
        }

        $metadata = $order->getAttribute('metadata');

        if (is_string($metadata) && trim($metadata) !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $reason = data_get($metadata, 'komerce.cancelled_reason');

        return is_string($reason) && trim($reason) !== '' ? $reason : null;
    }

    /**
     * Customer/admin facing sentence for a cancelled order.
     * Storefront says "oleh Anda"; Shopper says "oleh pelanggan".
     */
    public static function cancelledReasonLabel(Order $order, string $audience = 'customer'): ?string
    {
        if ($order->status !== OrderStatus::Cancelled) {
            return null;
        }

        $reason = self::cancelledReason($order);

        return match ($reason) {
            'Payment expired' => 'Pesanan dibatalkan otomatis karena pembayaran kedaluwarsa.',
            'Cancelled by customer' => $audience === 'admin'
                ? 'Pesanan dibatalkan oleh pelanggan.'
                : 'Pesanan dibatalkan oleh Anda.',
            default => $reason
                ? "Pesanan dibatalkan: {$reason}"
                : 'Pesanan dibatalkan.',
        };
    }
}
