<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Komerce;

use App\Exceptions\KomerceNotConfiguredException;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

final class ShippingDeliveryClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.shipping_delivery_api_key', 'delivery-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    public function test_search_destinations_uses_documented_endpoint_and_delivery_key(): void
    {
        Http::fake([
            'https://delivery.example.test/tariff/api/v1/destination/search*' => Http::response([
                'meta' => ['code' => 200],
                'data' => [
                    ['id' => 152, 'label' => 'Gambir, Jakarta Pusat'],
                ],
            ]),
        ]);

        $response = (new ShippingDeliveryClient)->searchDestinations('Gambir');

        $this->assertSame(152, $response['data'][0]['id']);
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://delivery.example.test/tariff/api/v1/destination/search')
                && $request->hasHeader('x-api-key', 'delivery-key')
                && $request['keyword'] === 'Gambir';
        });
    }

    public function test_calculate_uses_kilograms_and_all_documented_query_fields(): void
    {
        Http::fake([
            'https://delivery.example.test/tariff/api/v1/calculate*' => Http::response([
                'meta' => ['code' => 200],
                'data' => ['calculate_reguler' => []],
            ]),
        ]);

        (new ShippingDeliveryClient)->calculate(
            shipperDestinationId: 501,
            receiverDestinationId: '152',
            originPinPoint: '-6.175392,106.827153',
            destinationPinPoint: '-6.200000,106.816666',
            weightKilograms: 1.25,
            itemValue: 150000,
            cod: true,
        );

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://delivery.example.test/tariff/api/v1/calculate')
                && $request['shipper_destination_id'] === 501
                && $request['receiver_destination_id'] === 152
                && $request['origin_pin_point'] === '-6.175392,106.827153'
                && $request['destination_pin_point'] === '-6.200000,106.816666'
                && (float) $request['weight'] === 1.25
                && $request['item_value'] === 150000
                && $request['cod'] === 'yes';
        });
    }

    public function test_calculate_omits_optional_cod_when_not_selected(): void
    {
        Http::fake([
            'https://delivery.example.test/tariff/api/v1/calculate*' => Http::response([
                'meta' => ['code' => 200],
                'data' => [],
            ]),
        ]);

        (new ShippingDeliveryClient)->calculate(
            501,
            152,
            '-6.175392,106.827153',
            '-6.200000,106.816666',
            1,
            150000,
        );

        Http::assertSent(fn (Request $request): bool => ! array_key_exists('cod', $request->data()));
    }

    public function test_detail_and_cancel_use_documented_order_contracts(): void
    {
        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/detail*' => Http::response([
                'meta' => ['code' => 200],
                'data' => ['order_no' => 'RO-001'],
            ]),
            'https://delivery.example.test/order/api/v1/orders/cancel' => Http::response([
                'meta' => ['code' => 200],
                'data' => ['order_no' => 'RO-001'],
            ]),
        ]);

        $client = new ShippingDeliveryClient;
        $this->assertSame('RO-001', data_get($client->detailOrder(' RO-001 '), 'data.order_no'));
        $this->assertSame('RO-001', data_get($client->cancelOrder(' RO-001 '), 'data.order_no'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://delivery.example.test/order/api/v1/orders/detail')
                && $request['order_no'] === 'RO-001';
        });
        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'PUT'
                && $request->url() === 'https://delivery.example.test/order/api/v1/orders/cancel'
                && $request->hasHeader('Content-Type', 'application/json')
                && $request->data() === ['order_no' => 'RO-001'];
        });
    }

    public function test_calculate_rejects_a_non_positive_kilogram_weight(): void
    {
        Http::fake();

        try {
            (new ShippingDeliveryClient)->calculate(
                501,
                152,
                '-6.175392,106.827153',
                '-6.200000,106.816666',
                0,
                150000,
            );
            $this->fail('Expected an invalid kilogram weight to be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('kilograms', $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_calculate_rejects_invalid_pin_point_coordinates(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid latitude or longitude');

        (new ShippingDeliveryClient)->calculate(
            501,
            152,
            '-96.175392,106.827153',
            '-6.200000,106.816666',
            1,
            150000,
        );
    }

    public function test_store_order_rejects_an_empty_payload(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);

        (new ShippingDeliveryClient)->storeOrder([]);
    }

    public function test_other_product_keys_do_not_enable_delivery_requests(): void
    {
        config()->set('komerce.shipping_delivery_api_key', '');
        config()->set('komerce.shipping_cost_api_key', 'cost-key');
        config()->set('komerce.payment_api_key', 'payment-key');
        Http::fake();

        try {
            (new ShippingDeliveryClient)->detailOrder('RO-001');
            $this->fail('Expected the missing Shipping Delivery credential to be rejected.');
        } catch (KomerceNotConfiguredException) {
            $this->addToAssertionCount(1);
        }

        Http::assertNothingSent();
    }
}
