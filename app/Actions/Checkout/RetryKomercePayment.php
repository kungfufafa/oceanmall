<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use RuntimeException;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Throwable;

final class RetryKomercePayment
{
    public function __construct(
        private readonly CreateKomercePayment $createPayment,
        private readonly ResolveKomercePaymentInstructions $resolveInstructions,
    ) {}

    /**
     * Recreate a Komerce VA/QRIS charge for an unpaid order.
     *
     * @return array<string, mixed>
     */
    public function handle(Order $order): array
    {
        if (! $this->resolveInstructions->canRetry($order)) {
            throw new RuntimeException('Order is not eligible for Komerce payment retry.');
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            throw new RuntimeException('Order is already paid.');
        }

        $order->loadMissing('paymentMethod');
        $method = $order->paymentMethod;

        if (! $method instanceof PaymentMethod || ($method->driver ?? null) !== 'komerce') {
            throw new RuntimeException('Order has no Komerce payment method.');
        }

        $selected = $this->selectedMethodPayload($method);

        try {
            return $this->createPayment->handle($order, $selected);
        } catch (Throwable $e) {
            report($e);

            throw new RuntimeException('Unable to recreate Komerce payment: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function selectedMethodPayload(PaymentMethod $method): array
    {
        $meta = $method->metadata;
        if (is_string($meta)) {
            $decoded = json_decode($meta, true);
            $meta = is_array($decoded) ? $decoded : [];
        }
        if (! is_array($meta)) {
            $meta = [];
        }

        return [
            'id' => $method->id,
            'driver' => 'komerce',
            'title' => $method->title,
            'channel_code' => $meta['channel_code'] ?? null,
            'payment_type' => $meta['payment_type'] ?? 'bank_transfer',
        ];
    }
}
