<?php

declare(strict_types=1);

use App\Actions\ZoneSessionManager;
use App\DTO\CountryByZoneData;
use App\Models\Channel;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Models\Cart;
use Shopper\Core\Models\TaxZone;

if (! function_exists('cartSession')) {
    function cartSession(): Cart
    {
        $session = resolve(CartSessionManager::class);
        $cart = $session->current();

        if (! $cart) {
            $zone = ZoneSessionManager::getSession();
            $defaultChannel = Channel::query()->scopes('default')->first();

            $cart = $session->create([
                'currency_code' => current_currency(),
                'channel_id' => $defaultChannel?->id,
                'zone_id' => $zone?->zoneId,
                'customer_id' => auth()->id(),
            ]);
        }

        return $cart;
    }
}

if (! function_exists('komerce_service_enabled')) {
    /**
     * Resolve one Komerce product independently from every other product key.
     * KOMERCE_ENABLED=false is a master kill switch; true does not make a
     * credential-less service ready.
     */
    function komerce_service_enabled(string $apiKeyConfig): bool
    {
        if (config('komerce.enabled') === false) {
            return false;
        }

        return trim((string) config($apiKeyConfig, '')) !== '';
    }
}

if (! function_exists('komerce_payment_enabled')) {
    function komerce_payment_enabled(): bool
    {
        return komerce_service_enabled('komerce.payment_api_key');
    }
}

if (! function_exists('komerce_shipping_cost_enabled')) {
    function komerce_shipping_cost_enabled(): bool
    {
        return komerce_service_enabled('komerce.shipping_cost_api_key');
    }
}

if (! function_exists('komerce_shipping_delivery_enabled')) {
    function komerce_shipping_delivery_enabled(): bool
    {
        return komerce_service_enabled('komerce.shipping_delivery_api_key');
    }
}

if (! function_exists('komerce_enabled')) {
    /**
     * Whether at least one independently configured Komerce product is ready.
     * Product-specific callers should use the corresponding readiness helper.
     */
    function komerce_enabled(): bool
    {
        if (config('komerce.enabled') === false) {
            return false;
        }

        return komerce_payment_enabled()
            || komerce_shipping_cost_enabled()
            || komerce_shipping_delivery_enabled()
            || qrisly_enabled();
    }
}

if (! function_exists('qrisly_enabled')) {
    /**
     * QRISLY product API is opt-in: both API key and merchant qris_id must be set.
     * When disabled, checkout QRIS falls back to Komerce Payment API (`payment_type=qris`).
     */
    function qrisly_enabled(): bool
    {
        return komerce_service_enabled('komerce.qrisly_api_key')
            && trim((string) config('komerce.qrisly_qris_id', '')) !== '';
    }
}

if (! function_exists('zero_decimal_currencies')) {
    /**
     * @return array<int, string>
     */
    function zero_decimal_currencies(): array
    {
        return [
            'IDR', 'BIF', 'CLP', 'DJF', 'GNF', 'HTG', 'JPY', 'KMF', 'KRW',
            'MGA', 'PYG', 'RWF', 'VND', 'VUV', 'XAF', 'XAG', 'XAU',
            'XDR', 'XOF', 'XPF',
        ];
    }
}

if (! function_exists('is_no_division_currency')) {
    function is_no_division_currency(string $currency): bool
    {
        return in_array($currency, zero_decimal_currencies(), true);
    }
}

if (! function_exists('current_currency')) {
    function current_currency(): string
    {
        return ZoneSessionManager::getSession()?->currencyCode ?? shopper_currency();
    }
}

if (! function_exists('current_tax_label')) {
    function current_tax_label(): string
    {
        return once(function (): string {
            $zone = ZoneSessionManager::getSession();

            if (! $zone instanceof CountryByZoneData) {
                return '';
            }

            $taxZone = TaxZone::query()
                ->whereHas('country', fn ($q) => $q->where('cca2', $zone->countryCode))
                ->whereNull('province_code')
                ->first();

            return $taxZone?->is_tax_inclusive
                ? 'termasuk pajak'
                : 'belum pajak';
        });
    }
}
