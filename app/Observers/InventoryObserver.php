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
        if (! komerce_shipping_cost_enabled()) {
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
        $candidates = array_values(array_filter(
            $results,
            static fn (mixed $row): bool => is_array($row)
                && isset($row['id'])
                && trim((string) $row['id']) !== '',
        ));

        if ($candidates === []) {
            return null;
        }

        $hasVerifiedMatch = false;

        if ($postalCode !== '') {
            $candidates = array_values(array_filter(
                $candidates,
                static fn (array $row): bool => trim((string) ($row['zip_code'] ?? '')) === $postalCode,
            ));

            if ($candidates === []) {
                return null;
            }

            $hasVerifiedMatch = true;
        }

        $normalizedStreet = $this->normalize($street);
        if ($normalizedStreet !== '') {
            $streetMatches = array_values(array_filter(
                $candidates,
                function (array $row) use ($normalizedStreet): bool {
                    foreach (['subdistrict_name', 'district_name'] as $field) {
                        $place = $this->normalize((string) ($row[$field] ?? ''));

                        if ($place !== '' && str_contains($normalizedStreet, $place)) {
                            return true;
                        }
                    }

                    return false;
                },
            ));

            if ($streetMatches !== []) {
                $candidates = $streetMatches;
                $hasVerifiedMatch = true;
            }
        }

        foreach ([
            'city_name' => $city,
            'province_name' => $state,
        ] as $field => $expected) {
            $expected = $this->normalize($expected);

            if ($expected === '') {
                continue;
            }

            $fieldMatches = array_values(array_filter(
                $candidates,
                function (array $row) use ($field, $expected): bool {
                    $actual = $this->normalize((string) ($row[$field] ?? ''));

                    return $actual !== ''
                        && (str_contains($actual, $expected) || str_contains($expected, $actual));
                },
            ));

            $knownFieldRows = array_filter(
                $candidates,
                fn (array $row): bool => $this->normalize((string) ($row[$field] ?? '')) !== '',
            );

            if ($knownFieldRows !== [] && $fieldMatches === []) {
                return null;
            }

            if ($fieldMatches === []) {
                continue;
            }

            $candidates = $fieldMatches;
            $hasVerifiedMatch = true;
        }

        // The search endpoint returns candidates, not a guaranteed canonical
        // match. Persist only one result supported by supplied address data.
        if (! $hasVerifiedMatch || count($candidates) !== 1) {
            return null;
        }

        return (string) $candidates[0]['id'];
    }

    private function normalize(string $value): string
    {
        return strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }
}
