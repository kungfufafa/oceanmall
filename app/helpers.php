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

if (! function_exists('komerce_enabled')) {
    /**
     * Whether the Komerce collaborator integration (payment + RajaOngkir shipping)
     * is active. When KOMERCE_ENABLED is not set explicitly, the integration is
     * considered enabled only when an API key is configured. This is the single
     * source of truth used to short-circuit every Komerce/RajaOngkir feature so
     * an unconfigured store never attempts an outbound call.
     */
    function komerce_enabled(): bool
    {
        $explicit = config('komerce.enabled');

        if ($explicit !== null) {
            return (bool) $explicit;
        }

        return trim((string) config('komerce.api_key', '')) !== '';
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

            return $taxZone?->is_tax_inclusive ? __('TTC') : __('HT');
        });
    }
}
