<?php

declare(strict_types=1);

namespace App\Services\Komerce;

use App\Services\Komerce\Concerns\UsesKomerceHttp;
use InvalidArgumentException;

final class ShippingDeliveryClient
{
    use UsesKomerceHttp;

    private const STORE_ORDER_ENDPOINT = '/order/api/v1/orders/store';

    private const REQUEST_PICKUP_ENDPOINT = '/order/api/v1/pickup/request';

    private const PRINT_LABEL_ENDPOINT = '/order/api/v1/orders/print-label';

    private const TRACK_ENDPOINT = '/order/api/v1/orders/history-airway-bill';

    /**
     * Supported RajaOngkir label page formats.
     *
     * @var list<string>
     */
    public const LABEL_PAGES = ['page_1', 'page_2', 'page_4', 'page_5', 'page_6'];

    public const DEFAULT_LABEL_PAGE = 'page_5';

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
     * Generate one or more shipping labels (resi) for RajaOngkir delivery orders.
     *
     * The order(s) must already have a scheduled pickup. Returns the API payload,
     * which contains a downloadable label path under `data.path`.
     *
     * @param  list<string>  $orderNos  One or more RajaOngkir delivery order numbers.
     * @return array<string, mixed>
     */
    public function printLabel(array $orderNos, string $page = self::DEFAULT_LABEL_PAGE): array
    {
        $orderNos = array_values(array_filter(
            array_map(static fn (mixed $orderNo): string => trim((string) $orderNo), $orderNos),
            static fn (string $orderNo): bool => $orderNo !== '',
        ));

        if ($orderNos === []) {
            throw new InvalidArgumentException('At least one delivery order number is required to print a label.');
        }

        if (! in_array($page, self::LABEL_PAGES, true)) {
            throw new InvalidArgumentException(sprintf('Unsupported label page format [%s].', $page));
        }

        $query = http_build_query([
            'order_no' => implode(',', $orderNos),
            'page' => $page,
        ]);

        $response = $this->deliveryHttp()
            ->post(self::PRINT_LABEL_ENDPOINT.'?'.$query)
            ->throw()
            ->json();

        return is_array($response) ? $response : [];
    }

    /**
     * Track an airway bill through the RajaOngkir delivery history endpoint.
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
