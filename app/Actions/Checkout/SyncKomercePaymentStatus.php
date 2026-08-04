<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Models\PaymentTransaction;

final class SyncKomercePaymentStatus
{
    public function __construct(
        private readonly ResolveKomercePaymentInstructions $resolveInstructions,
        private readonly MarkOrderPaidFromKomerce $markOrderPaid,
    ) {}

    /**
     * Ask Komerce for the latest status and mark the order paid when confirmed.
     *
     * @return 'already_paid'|'handled'|'not_paid'|'no_payment'
     */
    public function handle(Order $order): string
    {
        $order->refresh();

        if ($order->payment_status === PaymentStatus::Paid) {
            return 'already_paid';
        }

        $paymentId = $this->resolvePaymentId($order);

        if ($paymentId === null) {
            return 'no_payment';
        }

        $provider = $this->resolveProvider($order);

        $result = $this->markOrderPaid->handle($paymentId, $provider);

        return match ($result) {
            'handled' => 'handled',
            'already_processed' => 'already_paid',
            'not_paid' => 'not_paid',
            default => 'not_paid',
        };
    }

    private function resolvePaymentId(Order $order): ?string
    {
        $instructions = $this->resolveInstructions->handle($order);
        $fromInstructions = data_get($instructions, 'payment_id');

        if (is_string($fromInstructions) && $fromInstructions !== '') {
            return $fromInstructions;
        }

        $transaction = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('driver', 'komerce')
            ->latest('id')
            ->first();

        if (is_string($transaction?->reference) && $transaction->reference !== '') {
            return $transaction->reference;
        }

        $metadata = $order->getAttribute('metadata');
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($metadata)) {
            return null;
        }

        foreach ([
            'komerce.payment_ref',
            'komerce.payment_instructions.payment_id',
            'komerce.qrisly_history_id',
            'komerce_payment_ref',
        ] as $path) {
            $value = data_get($metadata, $path);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return null;
    }

    private function resolveProvider(Order $order): string
    {
        $instructions = $this->resolveInstructions->handle($order);
        $fromInstructions = data_get($instructions, 'provider');

        if (is_string($fromInstructions) && $fromInstructions !== '') {
            return $fromInstructions;
        }

        $metadata = $order->getAttribute('metadata');
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        $fromOrder = is_array($metadata) ? data_get($metadata, 'komerce.provider') : null;

        return is_string($fromOrder) && $fromOrder !== '' ? $fromOrder : 'payment_api';
    }
}
