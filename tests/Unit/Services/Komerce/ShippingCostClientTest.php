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
}
