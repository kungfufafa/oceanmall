<?php

declare(strict_types=1);

namespace App\Payment;

use Shopper\Payment\DataTransferObjects\PaymentResult;
use Shopper\Payment\Drivers\Driver;
use Shopper\Payment\Exceptions\PaymentException;

/**
 * Shopper payment driver registration for Komerce.
 *
 * Checkout still uses CreateKomercePayment / webhooks; this driver exists so
 * Shopper /cpanel can resolve logos and method metadata for driver=komerce.
 */
final class KomerceDriver extends Driver
{
    public function code(): string
    {
        return 'komerce';
    }

    public function name(): string
    {
        return 'Komerce';
    }

    public function logo(): ?string
    {
        return null;
    }

    public function isConfigured(): bool
    {
        return komerce_payment_enabled() || qrisly_enabled();
    }

    public function supportsWebhooks(): bool
    {
        return true;
    }

    public function supportsRefunds(): bool
    {
        return false;
    }

    public function initiatePayment(int $amount, string $currency, array $context = []): PaymentResult
    {
        throw PaymentException::notSupported('initiatePayment', $this->code());
    }
}
