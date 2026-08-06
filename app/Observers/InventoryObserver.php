<?php

declare(strict_types=1);

namespace App\Observers;

use App\Services\Komerce\ShippingCostClient;
use Shopper\Core\Models\Inventory;
use Throwable;

final class InventoryObserver
{
    public function saving(Inventory $inventory): void
    {
        if (! komerce_enabled()) {
            return;
        }

        $currentOrigin = $inventory->getAttribute('rajaongkir_origin_id');
        if (is_scalar($currentOrigin) && trim((string) $currentOrigin) !== '') {
            return;
        }

        $postalCode = trim((string) $inventory->getAttribute('postal_code'));
        $city = trim((string) $inventory->getAttribute('city'));
        $street = trim((string) $inventory->getAttribute('street_address'));
        $state = trim((string) $inventory->getAttribute('state'));

        if ($postalCode === '' && $city === '' && $street === '') {
            return;
        }

        try {
            $client = resolve(ShippingCostClient::class);

            $results = [];
            if ($postalCode !== '') {
                $results = $client->searchDomestic($postalCode, 50);
            }

            if (empty($results) && ($city !== '' || $street !== '')) {
                $query = trim("{$street} {$city}");
                $results = $client->searchDomestic($query, 50);
            }

            if (empty($results) && $city !== '') {
                $results = $client->searchDomestic($city, 50);
            }

            if (empty($results)) {
                return;
            }

            $bestId = $this->findBestOriginId($results, $street, $city, $state, $postalCode);

            if ($bestId !== null) {
                $inventory->setAttribute('rajaongkir_origin_id', $bestId);
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * @param  list<array<string, mixed>>  $results
     */
    private function findBestOriginId(array $results, string $street, string $city, string $state, string $postalCode): ?string
    {
        $normalizedStreet = strtolower($street);
        $normalizedCity = strtolower($city);

        // 1. Try to find a row where subdistrict or district name appears in street address
        foreach ($results as $row) {
            $subdistrict = strtolower((string) ($row['subdistrict_name'] ?? ''));
            $district = strtolower((string) ($row['district_name'] ?? ''));

            if ($subdistrict !== '' && str_contains($normalizedStreet, $subdistrict)) {
                return (string) $row['id'];
            }

            if ($district !== '' && str_contains($normalizedStreet, $district)) {
                return (string) $row['id'];
            }
        }

        // 2. Try to find a row matching city name and postal code
        foreach ($results as $row) {
            $rowCity = strtolower((string) ($row['city_name'] ?? ''));
            $rowZip = (string) ($row['zip_code'] ?? '');

            if ($rowCity !== '' && (str_contains($normalizedCity, $rowCity) || str_contains($rowCity, $normalizedCity)) && ($postalCode === '' || $rowZip === $postalCode)) {
                return (string) $row['id'];
            }
        }

        // 3. Fallback to first result's ID
        return isset($results[0]['id']) ? (string) $results[0]['id'] : null;
    }
}
