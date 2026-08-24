<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Support\EnabledCarriers;
use App\Support\KomerceCourierAssets;
use App\Support\RajaOngkirQuoteContext;
use Illuminate\Support\Collection;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Zone;
use Shopper\Shipping\DataTransferObjects\Address as ShippingAddress;
use Shopper\Shipping\DataTransferObjects\Package;
use Shopper\Shipping\DataTransferObjects\ShippingRate;
use Shopper\Shipping\Facades\Shipping;
use Shopper\Shipping\Services\CarrierRateService;
use Throwable;

final class FetchDeliveryRates
{
    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<int, Package>  $packages
     * @return array<int, array<string, mixed>>
     */
    public function handle(array $shippingAddress, array $packages, ?int $originInventoryId = null): array
    {
        $rajaOngkirRates = $this->rajaOngkirRates($shippingAddress, $packages, $originInventoryId);

        if ($rajaOngkirRates !== null) {
            return $rajaOngkirRates;
        }

        $countryId = $shippingAddress['country_id'] ?? null;

        if (! $countryId) {
            return [];
        }

        $zone = resolve(ResolveZoneForCountry::class)->handle($countryId);

        if (! $zone) {
            return [];
        }

        $service = resolve(CarrierRateService::class);

        try {
            $rates = $service->getRatesForZone(
                zone: $zone,
                from: $this->buildOriginAddress(),
                to: $this->buildDestinationAddress($shippingAddress),
                packages: $packages,
            );
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return $this->formatRates($rates, $zone, $service);
    }

    /**
     * Quote RajaOngkir Cost rates through Shopper's registered `rajaongkir` driver.
     *
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<int, Package>  $packages
     * @return array<int, array<string, mixed>>|null
     */
    private function rajaOngkirRates(array $shippingAddress, array $packages, ?int $originInventoryId = null): ?array
    {
        if (! komerce_shipping_cost_enabled()) {
            return null;
        }

        $originId = $originInventoryId !== null
            ? $this->inventoryOriginId($originInventoryId)
            : $this->defaultInventoryOriginId();
        $destinationId = $shippingAddress['rajaongkir_destination_id']
            ?? $shippingAddress['destination_id']
            ?? null;

        if (! $originId || ! $destinationId) {
            return [];
        }

        $countryId = isset($shippingAddress['country_id']) ? (int) $shippingAddress['country_id'] : null;
        $allSlugs = $this->couriers($countryId > 0 ? $countryId : null);
        /** @var list<string> $validKomerceCouriers */
        $validKomerceCouriers = array_map('strtolower', (array) config('komerce.couriers', []));
        $courierSlugs = array_values(array_filter(
            $allSlugs,
            static fn (string $slug): bool => in_array(strtolower($slug), $validKomerceCouriers, true),
        ));

        if ($courierSlugs === []) {
            return [];
        }

        $context = resolve(RajaOngkirQuoteContext::class);
        $context->set((string) $originId, (string) $destinationId, $courierSlugs);

        try {
            $rates = Shipping::driver('rajaongkir')->calculateRates(
                from: $this->buildOriginAddress(),
                to: $this->buildDestinationAddress($shippingAddress),
                packages: $packages,
            );
        } catch (Throwable $e) {
            report($e);

            return [];
        } finally {
            $context->clear();
        }

        return $this->formatShopperRates($rates);
    }

    private function defaultInventoryOriginId(): ?string
    {
        $inventory = Inventory::query()
            ->where('is_default', true)
            ->first();

        $originId = $inventory?->getAttribute('rajaongkir_origin_id');

        return $originId ? (string) $originId : null;
    }

    private function inventoryOriginId(int $inventoryId): ?string
    {
        $inventory = Inventory::query()->find($inventoryId);
        $originId = $inventory?->getAttribute('rajaongkir_origin_id');

        return $originId ? (string) $originId : null;
    }

    /**
     * @return list<string>
     */
    private function couriers(?int $countryId = null): array
    {
        $zone = $countryId !== null
            ? resolve(ResolveZoneForCountry::class)->handle($countryId)
            : null;

        return EnabledCarriers::rajaOngkirSlugs($zone);
    }

    /**
     * @param  Collection<int, ShippingRate>|mixed  $rates
     * @return array<int, array<string, mixed>>
     */
    private function formatShopperRates(mixed $rates): array
    {
        if (! is_iterable($rates)) {
            return [];
        }

        $dbCarriers = Carrier::query()->get()->keyBy(fn (Carrier $c): string => strtolower((string) $c->slug));
        $formatted = [];

        foreach ($rates as $rate) {
            if (! $rate instanceof ShippingRate) {
                continue;
            }

            $carrierCode = strtolower($rate->carrierCode);
            $dbCarrier = $dbCarriers->get($carrierCode)
                ?? $dbCarriers->get($carrierCode === 'idexpress' ? 'ide' : ($carrierCode === 'ide' ? 'idexpress' : $carrierCode));
            $logoUrl = data_get($dbCarrier?->metadata, 'logo_url')
                ?? $dbCarrier?->logo()
                ?? KomerceCourierAssets::logoUrl($carrierCode);

            $formatted[] = [
                'service_code' => $rate->serviceCode,
                'service_name' => $rate->serviceName,
                'amount' => $rate->amount,
                'currency' => $rate->currency,
                'carrier_code' => $carrierCode,
                'estimated_days' => $rate->estimatedDays,
                'description' => null,
                'carrier_name' => $dbCarrier?->name ?? $rate->carrierCode,
                'carrier_logo' => $logoUrl,
            ];
        }

        return $formatted;
    }

    private function buildOriginAddress(): ShippingAddress
    {
        return once(function (): ShippingAddress {
            $countryId = shopper_setting('country_id');
            $country = $countryId ? Country::query()->find($countryId) : null;

            return new ShippingAddress(
                firstName: shopper_setting('name') ?? '',
                lastName: '',
                street: shopper_setting('street_address') ?? '',
                city: shopper_setting('city') ?? '',
                postalCode: shopper_setting('postal_code') ?? '',
                state: shopper_setting('state') ?? '',
                country: $country?->cca2 ?? '',
                phone: shopper_setting('phone_number'),
            );
        });
    }

    /**
     * @param  array<string, mixed>  $shippingAddress
     */
    private function buildDestinationAddress(array $shippingAddress): ShippingAddress
    {
        $country = Country::query()->find($shippingAddress['country_id'] ?? null);

        return new ShippingAddress(
            firstName: $shippingAddress['first_name'] ?? '',
            lastName: $shippingAddress['last_name'] ?? '',
            street: $shippingAddress['street_address'] ?? '',
            city: $shippingAddress['city'] ?? '',
            postalCode: $shippingAddress['postal_code'] ?? '',
            state: $shippingAddress['state'] ?? '',
            country: $country?->cca2 ?? '',
            street2: $shippingAddress['street_address_plus'] ?? null,
            phone: $shippingAddress['phone_number'] ?? null,
            email: auth()->user()?->email,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function formatRates(mixed $rates, Zone $zone, CarrierRateService $service): array
    {
        $carriers = $zone->carriers()
            ->where('is_enabled', true)
            ->get()
            ->keyBy(fn (Carrier $carrier): string => $carrier->slug ?? $carrier->name);

        $carrierOptions = $zone->shippingOptions()
            ->where('is_enabled', true)
            ->get()
            ->keyBy('id');

        return $rates->map(function ($rate) use ($carriers, $carrierOptions, $service): array {
            $carrier = $carriers->get($rate->carrierCode);
            $option = is_int($rate->serviceCode) ? $carrierOptions->get($rate->serviceCode) : null;

            return [
                'service_code' => $rate->serviceCode,
                'service_name' => $rate->serviceName,
                'amount' => $rate->amount,
                'currency' => $rate->currency,
                'carrier_code' => $rate->carrierCode,
                'estimated_days' => $rate->estimatedDays,
                'description' => $option?->description,
                'carrier_name' => $carrier?->name ?? $rate->carrierCode,
                'carrier_logo' => $carrier ? $service->getLogoUrl($carrier) : null,
            ];
        })->values()->all();
    }
}
