<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;

final class ShippingDeliveryClient
{
    use UsesKomerceHttp;

    private const STORE_ORDER_ENDPOINT = '/order/api/v1/orders/store';

    private const REQUEST_PICKUP_ENDPOINT = '/order/api/v1/pickup/request';

    private const TRACK_ENDPOINT = '/order/api/v1/history-airway-bill';

    /**
     * Store a RajaOngkir delivery order.
     *
     * Assumed payload includes order_no, origin_id, destination_id,
     * payment_method, service_fee, receiver{...}, shipping{...}, items, and
     * total_weight.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function storeOrder(array $payload): array
    {
        $response = $this->deliveryHttp()
            ->post(self::STORE_ORDER_ENDPOINT, $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * Request pickup for one or more RajaOngkir delivery orders.
     *
     * Assumed payload includes order_no and may include awb or pickup metadata.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function requestPickup(array $payload): array
    {
        $response = $this->deliveryHttp()
            ->post(self::REQUEST_PICKUP_ENDPOINT, $payload)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * Track an airway bill when the delivery API supports it.
     *
     * @return array<string, mixed>
     */
    public function track(string $awb): array
    {
        $response = $this->deliveryHttp()
            ->get(self::TRACK_ENDPOINT, ['awb' => $awb])
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }
}
