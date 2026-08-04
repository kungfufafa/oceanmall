<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class ResolveKomercePaymentInstructions
{
    /**
     * Rebuild customer-facing payment instructions from order metadata.
     *
     * @return array<string, mixed>|null
     */
    public function handle(Order $order): ?array
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return null;
        }

        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $instructions = data_get($metadata, 'komerce.payment_instructions');

        if (! is_array($instructions) || blank(data_get($instructions, 'payment_id'))) {
            return null;
        }

        return [
            'payment_id' => (string) $instructions['payment_id'],
            'payment_type' => (string) ($instructions['payment_type'] ?? 'bank_transfer'),
            'provider' => (string) ($instructions['provider'] ?? 'payment_api'),
            'virtual_account_number' => $instructions['virtual_account_number'] ?? null,
            'bank_code' => $instructions['bank_code'] ?? null,
            'qris_string' => $instructions['qris_string'] ?? null,
            'expiry_date' => $instructions['expiry_date'] ?? null,
            'amount' => (int) ($instructions['amount'] ?? $order->price_amount),
            'currency_code' => (string) ($instructions['currency_code'] ?? $order->currency_code ?? 'IDR'),
        ];
    }

    public function canRetry(Order $order): bool
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return false;
        }

        if (! komerce_enabled()) {
            return false;
        }

        $order->loadMissing('paymentMethod');

        return ($order->paymentMethod?->driver ?? null) === 'komerce'
            || filled(data_get($this->decodeMetadata($order->getAttribute('metadata')), 'komerce'));
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }
}
