<?php

declare(strict_types=1);

namespace App\Actions;

use App\CheckoutSession;
use App\DTO\CountryByZoneData;
use Illuminate\Support\Facades\Cache;
use Shopper\Cart\CartSessionManager;

final class ZoneSessionManager
{
    private const string KEY = 'zone_country_code';

    public static function checkSession(): bool
    {
        return session()->exists(self::KEY) && self::getSession() !== null;
    }

    public static function setSession(CountryByZoneData $zone): void
    {
        session()->put(self::KEY, $zone->countryCode);
    }

    public static function getSession(): ?CountryByZoneData
    {
        $countryCode = session()->get(self::KEY);

        if (! is_string($countryCode) || $countryCode === '') {
            return null;
        }

        return resolve(GetCountriesByZone::class)
            ->handle()
            ->firstWhere('countryCode', $countryCode);
    }

    /**
     * Ensure a shipping zone is selected. OceanMall is Indonesia-first — if the
     * visitor never picked a country, default to the store country (or ID).
     *
     * Unlike setSessionForCountryCode(), this does not wipe checkout state.
     */
    public static function ensureSession(?string $fallbackCountryCode = null): ?CountryByZoneData
    {
        if ($existing = self::getSession()) {
            return $existing;
        }

        $countries = resolve(GetCountriesByZone::class)->handle();

        if ($countries->isEmpty()) {
            return null;
        }

        $fallbackCountryCode ??= self::defaultCountryCode();

        $zone = $countries->firstWhere('countryCode', $fallbackCountryCode)
            ?? $countries->first();

        if (! $zone instanceof CountryByZoneData) {
            return null;
        }

        self::setSession($zone);

        $cart = resolve(CartSessionManager::class)->current();

        if ($cart && $cart->zone_id !== $zone->zoneId) {
            $cart->update([
                'zone_id' => $zone->zoneId,
                'currency_code' => $zone->currencyCode,
            ]);
        }

        return $zone;
    }

    private static function defaultCountryCode(): string
    {
        $countryId = shopper_setting('country_id');

        if ($countryId) {
            $code = \Shopper\Core\Models\Country::query()
                ->whereKey($countryId)
                ->value('cca2');

            if (is_string($code) && $code !== '') {
                return $code;
            }
        }

        return 'ID';
    }

    public static function setSessionForCountryCode(string $countryCode): ?CountryByZoneData
    {
        $zone = resolve(GetCountriesByZone::class)
            ->handle()
            ->firstWhere('countryCode', $countryCode);

        if (! $zone) {
            return null;
        }

        $current = self::getSession();

        if ($current && $current->countryId === $zone->countryId) {
            return $zone;
        }

        $oldCurrency = current_currency();

        self::setSession($zone);

        session()->forget(CheckoutSession::KEY);

        $cart = resolve(CartSessionManager::class)->current();

        if ($cart) {
            $cart->update([
                'zone_id' => $zone->zoneId,
                'currency_code' => $zone->currencyCode,
            ]);
        }

        Cache::forget("home_featured_products_{$oldCurrency}");
        Cache::forget("home_featured_products_{$zone->currencyCode}");

        return $zone;
    }
}
