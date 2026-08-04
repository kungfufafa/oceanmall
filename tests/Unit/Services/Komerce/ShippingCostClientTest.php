<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Komerce;

use App\Services\Komerce\ShippingCostClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class ShippingCostClientTest extends TestCase
{
    public function test_calculate_posts_form_payload_with_shipping_key_header(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
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

    public function test_calculate_uses_shipping_cost_api_key_over_legacy(): void
    {
        config()->set('komerce.api_key', 'legacy-key');
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
        config()->set('komerce.api_key', 'test-komerce-key');
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
}
