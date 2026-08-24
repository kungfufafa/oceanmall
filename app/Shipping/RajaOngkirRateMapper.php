<?php

declare(strict_types=1);

namespace App\Shipping;

use Illuminate\Support\Collection;
use Shopper\Shipping\DataTransferObjects\ShippingRate;

/**
 * Maps RajaOngkir Shipping Cost API V2 rows onto Shopper ShippingRate DTOs.
 *
 * Official V2 returns one flat row per service (`code`, `service`, `cost`, `etd`).
 */
final class RajaOngkirRateMapper
{
    /**
     * @param  array<string, mixed>  $response
     * @param  list<string>  $enabledCouriers
     * @return Collection<int, ShippingRate>
     */
    public function toShopperRates(array $response, array $enabledCouriers = []): Collection
    {
        $data = $response['data'] ?? [];

        if (! is_array($data)) {
            return collect();
        }

        $allowed = $enabledCouriers === []
            ? null
            : array_fill_keys(array_map('strtolower', $enabledCouriers), true);

        $rates = collect();

        foreach ($data as $carrierRow) {
            if (! is_array($carrierRow)) {
                continue;
            }

            $carrierCode = strtolower(trim((string) ($carrierRow['code'] ?? '')));

            if ($carrierCode === '' || ($allowed !== null && ! isset($allowed[$carrierCode]))) {
                continue;
            }

            $service = trim((string) ($carrierRow['service'] ?? ''));
            $amount = $this->costAmount($carrierRow);

            if ($service === '' || $amount === null) {
                continue;
            }

            $rates->push(new ShippingRate(
                serviceCode: "{$carrierCode}:{$service}",
                serviceName: $service,
                amount: $amount,
                currency: 'IDR',
                carrierCode: $carrierCode,
                estimatedDays: $this->estimatedDays($carrierRow),
            ));
        }

        return $rates->values();
    }

    /**
     * @param  array<string, mixed>  $costRow
     */
    private function costAmount(array $costRow): ?int
    {
        $cost = $costRow['cost'] ?? null;

        return is_numeric($cost) ? (int) $cost : null;
    }

    /**
     * @param  array<string, mixed>  $costRow
     */
    private function estimatedDays(array $costRow): ?string
    {
        if (! array_key_exists('etd', $costRow) || ! is_scalar($costRow['etd'])) {
            return null;
        }

        $etd = trim((string) $costRow['etd']);

        return $etd !== '' ? $etd : null;
    }
}
