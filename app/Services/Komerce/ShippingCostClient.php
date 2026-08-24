<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;
use InvalidArgumentException;

final class ShippingCostClient
{
    use UsesKomerceHttp;

    private const DOMESTIC_COST_ENDPOINT = '/api/v1/calculate/domestic-cost';

    private const INTERNATIONAL_COST_ENDPOINT = '/api/v1/calculate/international-cost';

    private const PROVINCE_ENDPOINT = '/api/v1/destination/province';

    private const DOMESTIC_DESTINATION_ENDPOINT = '/api/v1/destination/domestic-destination';

    private const INTERNATIONAL_DESTINATION_ENDPOINT = '/api/v1/destination/international-destination';

    private const TRACK_WAYBILL_ENDPOINT = '/api/v1/track/waybill';

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
     * List provinces for the official step-by-step destination method.
     *
     * Official: GET /destination/province with header `key`.
     *
     * @return list<array<string, mixed>>
     */
    public function searchProvinces(): array
    {
        $response = $this->shippingCostHttp()->get(self::PROVINCE_ENDPOINT);

        if ($response->status() === 404) {
            return [];
        }

        $json = $response->throw()->json();
        $rows = data_get($json, 'data', []);

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, static fn (mixed $row): bool => is_array($row)));
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
                'limit' => max(1, min($limit, 99)),
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

    /**
     * Search international destinations (countries) by nation name.
     *
     * Official: GET /destination/international-destination
     *
     * @return list<array{country_id: string, country_name: string}>
     */
    public function searchInternational(string $query, int $limit = 10, int $offset = 0): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $response = $this->shippingCostHttp()
            ->get(self::INTERNATIONAL_DESTINATION_ENDPOINT, [
                'search' => $query,
                'limit' => max(1, min($limit, 99)),
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
            ->filter(static fn (mixed $row): bool => is_array($row) && isset($row['country_id']))
            ->map(static function (array $row): array {
                return [
                    'country_id' => (string) $row['country_id'],
                    'country_name' => (string) ($row['country_name'] ?? $row['country_id']),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Calculate international shipping costs.
     *
     * Official: POST /calculate/international-cost as x-www-form-urlencoded.
     * Origin is a domestic destination id; destination is a country_id.
     * Weight is grams. Courier is a RajaOngkir courier code (colon-separated).
     *
     * @param  array{id: int|string}  $origin
     * @param  array{id: int|string}  $destination
     * @param  array<int, string>  $couriers
     * @return array<string, mixed>
     */
    public function calculateInternational(
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
            ->post(self::INTERNATIONAL_COST_ENDPOINT, $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * Track a waybill via Shipping Cost API V2.
     *
     * Official method is POST /track/waybill (not GET). Curl sends `awb` and
     * `courier` as query parameters; the body table also lists them as
     * x-www-form-urlencoded, plus `last_phone_number` (last 5 digits, required
     * by couriers such as JNE).
     *
     * @return array<string, mixed>
     */
    public function trackWaybill(string $awb, string $courier, ?string $lastPhoneNumber = null): array
    {
        $awb = trim($awb);
        $courier = strtolower(trim($courier));

        if ($awb === '') {
            throw new InvalidArgumentException('Shipping Cost tracking requires an airway bill.');
        }

        if ($courier === '' || preg_match('/^[a-z0-9_-]+$/', $courier) !== 1) {
            throw new InvalidArgumentException('Shipping Cost tracking courier must be a RajaOngkir courier code.');
        }

        $body = [
            'awb' => $awb,
            'courier' => $courier,
        ];

        $lastPhoneNumber = $lastPhoneNumber !== null ? preg_replace('/\D+/', '', $lastPhoneNumber) : '';
        $lastPhoneNumber = is_string($lastPhoneNumber) ? substr($lastPhoneNumber, -5) : '';
        if ($lastPhoneNumber !== '') {
            $body['last_phone_number'] = (int) $lastPhoneNumber;
        }

        $response = $this->shippingCostHttp()
            ->withQueryParameters($body)
            ->post(self::TRACK_WAYBILL_ENDPOINT, $body)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
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
