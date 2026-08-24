<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Komerce;

use App\Exceptions\KomerceNotConfiguredException;
use App\Services\Komerce\ShippingCostClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ShippingCostClientTest extends TestCase
{
    public function test_calculate_posts_form_payload_with_shipping_key_header(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
        config()->set('komerce.timeout', 15);

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => [
                    'code' => 200,
                    'message' => 'Success',
                ],
                'data' => [
                    [
                        'name' => 'Jalur Nugraha Ekakurir (JNE)',
                        'code' => 'jne',
                        'costs' => [
                            [
                                'service' => 'REG',
                                'cost' => 18000,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $response = (new ShippingCostClient)->calculate(
            origin: ['id' => 501],
            destination: ['id' => 114],
            weightGrams: 1200,
            couriers: ['jne', 'jnt', 'sicepat'],
        );

        $this->assertSame(200, $response['meta']['code']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://shipping.example.test/api/v1/calculate/domestic-cost'
                && $request->hasHeader('key', 'test-komerce-key')
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
                && $request['origin'] === 501
                && $request['destination'] === 114
                && $request['weight'] === 1200
                && $request['courier'] === 'jne:jnt:sicepat';
        });
    }

    public function test_calculate_uses_only_the_shipping_cost_api_key(): void
    {
        config()->set('komerce.api_key', 'legacy-key-that-must-be-ignored');
        config()->set('komerce.shipping_cost_api_key', 'shipping-cost-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200],
                'data' => [],
            ]),
        ]);

        (new ShippingCostClient)->calculate(
            origin: ['id' => 1],
            destination: ['id' => 2],
            weightGrams: 500,
            couriers: ['jne'],
        );

        Http::assertSent(fn (Request $request): bool => $request->hasHeader('key', 'shipping-cost-key'));
    }

    public function test_search_domestic_fetches_destination_results(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'id' => 114,
                        'label' => 'Jakarta Pusat, Gambir',
                        'province_name' => 'DKI Jakarta',
                        'city_name' => 'Jakarta Pusat',
                        'district_name' => 'Gambir',
                        'subdistrict_name' => 'Gambir',
                        'zip_code' => '10110',
                    ],
                ],
            ]),
        ]);

        $results = (new ShippingCostClient)->searchDomestic('jakarta', 5);

        $this->assertCount(1, $results);
        $this->assertSame('114', $results[0]['id']);
        $this->assertSame('Jakarta Pusat, Gambir', $results[0]['label']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/v1/destination/domestic-destination')
                && $request['search'] === 'jakarta'
                && (int) $request['limit'] === 5;
        });
    }

    #[DataProvider('documentedPriceFilters')]
    public function test_calculate_sends_documented_price_filter(string $price): void
    {
        config()->set('komerce.shipping_cost_api_key', 'shipping-cost-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200],
                'data' => [],
            ]),
        ]);

        (new ShippingCostClient)->calculate(
            origin: ['id' => 1],
            destination: ['id' => 2],
            weightGrams: 500,
            couriers: ['jne'],
            price: $price,
        );

        Http::assertSent(fn (Request $request): bool => $request['price'] === $price);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function documentedPriceFilters(): array
    {
        return [
            'lowest' => ['lowest'],
            'highest' => ['highest'],
        ];
    }

    public function test_calculate_rejects_undocumented_price_filter(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'shipping-cost-key');
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('lowest or highest');

        (new ShippingCostClient)->calculate(['id' => 1], ['id' => 2], 500, ['jne'], 'cheapest');
    }

    public function test_calculate_rejects_non_positive_gram_weight_before_sending(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'shipping-cost-key');
        Http::fake();

        try {
            (new ShippingCostClient)->calculate(['id' => 1], ['id' => 2], 0, ['jne']);
            $this->fail('Expected an invalid gram weight to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('grams', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_other_product_keys_do_not_enable_shipping_cost_requests(): void
    {
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.shipping_delivery_api_key', 'delivery-key');
        config()->set('komerce.shipping_cost_api_key', '');
        Http::fake();

        try {
            (new ShippingCostClient)->calculate(['id' => 1], ['id' => 2], 500, ['jne']);
            $this->fail('Expected the missing Shipping Cost credential to be rejected.');
        } catch (KomerceNotConfiguredException) {
            $this->addToAssertionCount(1);
        }

        Http::assertNothingSent();
    }

    public function test_track_waybill_posts_official_query_with_cost_key_header(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/track/waybill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success', 'message' => 'Success Track Waybill'],
                'data' => [
                    'delivered' => true,
                    'summary' => ['waybill_number' => 'JNE123', 'courier_code' => 'jne'],
                    'delivery_status' => ['status' => 'DELIVERED'],
                    'manifest' => [],
                ],
            ]),
        ]);

        $response = (new ShippingCostClient)->trackWaybill('JNE123', 'jne', '081234567890');

        $this->assertTrue(data_get($response, 'data.delivered'));

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://shipping.example.test/api/v1/track/waybill')
                && $request->hasHeader('key', 'test-komerce-key')
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
                && ($query['awb'] ?? null) === 'JNE123'
                && ($query['courier'] ?? null) === 'jne'
                && (int) ($query['last_phone_number'] ?? 0) === 67890
                && $request['awb'] === 'JNE123'
                && $request['courier'] === 'jne'
                && (int) $request['last_phone_number'] === 67890;
        });
    }

    public function test_search_provinces_gets_official_province_endpoint(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/province' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['id' => 9, 'name' => 'JAWA BARAT'],
                ],
            ]),
        ]);

        $rows = (new ShippingCostClient)->searchProvinces();

        $this->assertSame(9, $rows[0]['id']);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && $request->url() === 'https://shipping.example.test/api/v1/destination/province'
                && $request->hasHeader('key', 'test-komerce-key');
        });
    }

    public function test_search_international_gets_official_destination_endpoint(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/international-destination*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success', 'message' => 'Success Get International Destination'],
                'data' => [
                    ['country_id' => 108, 'country_name' => 'JAPAN'],
                ],
            ]),
        ]);

        $rows = (new ShippingCostClient)->searchInternational('japan', 5);

        $this->assertSame('108', $rows[0]['country_id']);
        $this->assertSame('JAPAN', $rows[0]['country_name']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_contains($request->url(), '/api/v1/destination/international-destination')
                && $request->hasHeader('key', 'test-komerce-key')
                && $request['search'] === 'japan';
        });
    }

    public function test_calculate_international_posts_form_payload(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/international-cost' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'JNE', 'code' => 'jne', 'service' => 'YES', 'cost' => 250000, 'currency' => 'IDR'],
                ],
            ]),
        ]);

        $response = (new ShippingCostClient)->calculateInternational(
            origin: ['id' => 17248],
            destination: ['id' => 108],
            weightGrams: 1000,
            couriers: ['jne'],
            price: 'lowest',
        );

        $this->assertSame(200, $response['meta']['code']);

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://shipping.example.test/api/v1/calculate/international-cost'
                && $request->hasHeader('key', 'test-komerce-key')
                && $request->hasHeader('Content-Type', 'application/x-www-form-urlencoded')
                && $request['origin'] === 17248
                && $request['destination'] === 108
                && $request['weight'] === 1000
                && $request['courier'] === 'jne'
                && $request['price'] === 'lowest';
        });
    }
}
