<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;
use InvalidArgumentException;

final class ShippingCostClient
{
    use UsesKomerceHttp;

    private const DOMESTIC_COST_ENDPOINT = '/api/v1/calculate/domestic-cost';

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
    public function calculate(array $origin, array $destination, int $weightGrams, array $couriers): array
    {
        if (! array_key_exists('id', $origin) || ! array_key_exists('id', $destination)) {
            throw new InvalidArgumentException('Origin and destination must include a RajaOngkir id.');
        }

        $response = $this->shippingCostHttp()
            ->post(self::DOMESTIC_COST_ENDPOINT, [
                'origin' => $origin['id'],
                'destination' => $destination['id'],
                'weight' => $weightGrams,
                'courier' => implode(':', $couriers),
            ])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }
}
