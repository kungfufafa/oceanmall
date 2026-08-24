<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Request-scoped provider for Shopper retrievePayment().
 *
 * Payment API and QRISLY use different status endpoints and must not share keys.
 */
final class KomercePaymentLookupContext
{
    private ?string $provider = null;

    public function setProvider(string $provider): void
    {
        $provider = strtolower(trim($provider));
        $this->provider = in_array($provider, ['payment_api', 'qrisly'], true) ? $provider : null;
    }

    public function provider(): string
    {
        return $this->provider ?? 'payment_api';
    }

    public function clear(): void
    {
        $this->provider = null;
    }
}
