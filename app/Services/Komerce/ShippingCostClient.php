<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;
use InvalidArgumentException;

final class ShippingCostClient
{
    use UsesKomerceHttp;

    private const DOMESTIC_COST_ENDPOINT = '/api/v1/calculate/domestic-cost';

    private const DOMESTIC_DESTINATION_ENDPOINT = '/api/v1/destination/domestic-destination';

    /**
     * Calculate domestic shipping costs with RajaOngkir.
     *
     * Origin and destination must include the RajaOngkir destination id in an
     * `id` key. Couriers are API courier codes such as jne, jnt, or sicepat.
     *
     * @param  array{id: int|string}  $origin
     * @param  array{id: int|string}  $destination
     * @param  array<int, string>  $couriers
     * @return array<string, mixed>
     */
    public function calculate(
        array $origin,
        array $destination,
        int $weightGrams,
        array $couriers,
        ?string $price = null,
    ): array {
        if (! array_key_exists('id', $origin) || ! array_key_exists('id', $destination)) {
            throw new InvalidArgumentException('Origin and destination must include a RajaOngkir id.');
        }

        $originId = $this->positiveDestinationId($origin['id'], 'Origin');
        $destinationId = $this->positiveDestinationId($destination['id'], 'Destination');

        if ($weightGrams <= 0) {
            throw new InvalidArgumentException('Shipping Cost weight must be greater than zero grams.');
        }

        $couriers = $this->normalizeCouriers($couriers);

        if ($price !== null && ! in_array($price, ['lowest', 'highest'], true)) {
            throw new InvalidArgumentException('Shipping Cost price must be either lowest or highest.');
        }

        $payload = [
            'origin' => $originId,
            'destination' => $destinationId,
            'weight' => $weightGrams,
            'courier' => implode(':', $couriers),
        ];

        if ($price !== null) {
            $payload['price'] = $price;
        }

        $response = $this->shippingCostHttp()
            ->post(self::DOMESTIC_COST_ENDPOINT, $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * Search domestic destinations (subdistricts) by keyword.
     *
     * @return list<array{id: string, label: string, province_name: string|null, city_name: string|null, district_name: string|null, subdistrict_name: string|null, zip_code: string|null}>
     */
    public function searchDomestic(string $query, int $limit = 10, int $offset = 0): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $response = $this->shippingCostHttp()
            ->get(self::DOMESTIC_DESTINATION_ENDPOINT, [
                'search' => $query,
                'limit' => max(1, min($limit, 50)),
                'offset' => max(0, $offset),
            ]);

        if ($response->status() === 404) {
            return [];
        }

        $json = $response->throw()->json();

        $rows = data_get($json, 'data', []);

        if (! is_array($rows)) {
            return [];
        }

        return collect($rows)
            ->filter(static fn (mixed $row): bool => is_array($row) && isset($row['id']))
            ->map(static function (array $row): array {
                return [
                    'id' => (string) $row['id'],
                    'label' => (string) ($row['label'] ?? $row['id']),
                    'province_name' => isset($row['province_name']) ? (string) $row['province_name'] : null,
                    'city_name' => isset($row['city_name']) ? (string) $row['city_name'] : null,
                    'district_name' => isset($row['district_name']) ? (string) $row['district_name'] : null,
                    'subdistrict_name' => isset($row['subdistrict_name']) ? (string) $row['subdistrict_name'] : null,
                    'zip_code' => isset($row['zip_code']) ? (string) $row['zip_code'] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function positiveDestinationId(mixed $value, string $field): int
    {
        if (is_bool($value)) {
            throw new InvalidArgumentException("{$field} RajaOngkir id must be a positive integer.");
        }

        $id = filter_var($value, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);

        if ($id === false) {
            throw new InvalidArgumentException("{$field} RajaOngkir id must be a positive integer.");
        }

        return $id;
    }

    /**
     * @param  array<int, string>  $couriers
     * @return list<string>
     */
    private function normalizeCouriers(array $couriers): array
    {
        $normalized = [];

        foreach ($couriers as $courier) {
            if (! is_string($courier)) {
                throw new InvalidArgumentException('Shipping Cost courier codes must be strings.');
            }

            $courier = strtolower(trim($courier));

            if ($courier === '' || preg_match('/^[a-z0-9_-]+$/', $courier) !== 1) {
                throw new InvalidArgumentException('Shipping Cost courier codes must contain only letters, numbers, underscores, or hyphens.');
            }

            $normalized[$courier] = true;
        }

        if ($normalized === []) {
            throw new InvalidArgumentException('At least one Shipping Cost courier code is required.');
        }

        return array_keys($normalized);
    }
}
