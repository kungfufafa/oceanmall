<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Services\Komerce\ShippingCostClient;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Zone;
use Shopper\Shipping\DataTransferObjects\Address as ShippingAddress;
use Shopper\Shipping\DataTransferObjects\Package;
use Shopper\Shipping\Services\CarrierRateService;
use Throwable;

final class FetchDeliveryRates
{
    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<int, Package>  $packages
     * @return array<int, array<string, mixed>>
     */
    public function handle(array $shippingAddress, array $packages): array
    {
        $rajaOngkirRates = $this->rajaOngkirRates($shippingAddress, $packages);

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
     * RajaOngkir Cost API response shape:
     * `{ meta: {...}, data: list<{ name, code, service?, description?, cost?, etd?, costs?: list<...> }> }`.
     *
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<int, Package>  $packages
     * @return array<int, array<string, mixed>>|null
     */
    private function rajaOngkirRates(array $shippingAddress, array $packages): ?array
    {
        if ((string) config('komerce.api_key', '') === '') {
            return null;
        }

        $originId = $this->defaultInventoryOriginId();
        $destinationId = $shippingAddress['rajaongkir_destination_id']
            ?? $shippingAddress['destination_id']
            ?? null;

        if (! $originId || ! $destinationId) {
            return null;
        }

        try {
            $response = resolve(ShippingCostClient::class)->calculate(
                origin: ['id' => $originId],
                destination: ['id' => $destinationId],
                weightGrams: $this->totalWeightGrams($packages),
                couriers: $this->couriers(),
            );
        } catch (Throwable $e) {
            report($e);

            return [];
        }

        return $this->formatRajaOngkirRates($response);
    }

    private function defaultInventoryOriginId(): ?string
    {
        $inventory = Inventory::query()
            ->where('is_default', true)
            ->first();

        $originId = $inventory?->getAttribute('rajaongkir_origin_id');

        return $originId ? (string) $originId : null;
    }

    /**
     * @return array<int, string>
     */
    private function couriers(): array
    {
        $couriers = config('komerce.couriers', ['jne', 'jnt', 'sicepat']);

        if (is_string($couriers)) {
            $couriers = explode(',', $couriers);
        }

        if (! is_array($couriers)) {
            return ['jne', 'jnt', 'sicepat'];
        }

        return array_values(array_filter(
            array_map(static fn (mixed $courier): string => trim((string) $courier), $couriers),
            static fn (string $courier): bool => $courier !== '',
        ));
    }

    /**
     * @param  array<int, Package>  $packages
     */
    private function totalWeightGrams(array $packages): int
    {
        $grams = array_sum(array_map(fn (Package $package): int => $this->packageWeightGrams($package), $packages));

        return max(1, (int) $grams);
    }

    private function packageWeightGrams(Package $package): int
    {
        $weight = max(0.0, $package->weight);

        if ($package->isImperial()) {
            return (int) round($weight * 453.59237);
        }

        if ($weight > 0 && $weight < 100) {
            return (int) round($weight * 1000);
        }

        return (int) round($weight);
    }

    /**
     * @param  array<string, mixed>  $response
     * @return array<int, array<string, mixed>>
     */
    private function formatRajaOngkirRates(array $response): array
    {
        $data = $response['data'] ?? [];

        if (! is_array($data)) {
            return [];
        }

        $rates = [];

        foreach ($data as $carrierRow) {
            if (! is_array($carrierRow)) {
                continue;
            }

            $carrierCode = (string) ($carrierRow['code'] ?? '');
            $carrierName = (string) ($carrierRow['name'] ?? $carrierCode);
            $costRows = isset($carrierRow['costs']) && is_array($carrierRow['costs'])
                ? $carrierRow['costs']
                : [$carrierRow];

            foreach ($costRows as $costRow) {
                if (! is_array($costRow)) {
                    continue;
                }

                $service = (string) ($costRow['service'] ?? '');
                $amount = $this->costAmount($costRow);

                if ($carrierCode === '' || $service === '' || $amount === null) {
                    continue;
                }

                $rates[] = [
                    'service_code' => "{$carrierCode}:{$service}",
                    'service_name' => $service,
                    'amount' => $amount,
                    'currency' => 'IDR',
                    'carrier_code' => $carrierCode,
                    'estimated_days' => $this->estimatedDays($costRow),
                    'description' => $costRow['description'] ?? null,
                    'carrier_name' => $carrierName,
                    'carrier_logo' => null,
                ];
            }
        }

        return $rates;
    }

    /**
     * @param  array<string, mixed>  $costRow
     */
    private function costAmount(array $costRow): ?int
    {
        $cost = $costRow['cost'] ?? null;

        if (is_array($cost)) {
            $cost = $cost['value'] ?? $cost[0]['value'] ?? null;
        }

        return is_numeric($cost) ? (int) $cost : null;
    }

    /**
     * @param  array<string, mixed>  $costRow
     */
    private function estimatedDays(array $costRow): string|int|null
    {
        if (array_key_exists('etd', $costRow)) {
            return $costRow['etd'];
        }

        $cost = $costRow['cost'] ?? null;

        if (is_array($cost)) {
            return $cost['etd'] ?? $cost[0]['etd'] ?? null;
        }

        return null;
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
