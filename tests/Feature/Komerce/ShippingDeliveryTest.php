<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Actions\Checkout\MarkOrderPaidFromKomerce;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderAddress;
use Shopper\Core\Models\OrderItem;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\TestCase;

final class ShippingDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeliveryConfig(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-komerce-key');
        config()->set('komerce.payment_api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        config()->set('komerce.pickup_time', '10:00:00');
        config()->set('komerce.pickup_vehicle', 'Motor');
    }

    public function test_delivery_client_posts_store_order_and_pickup_request(): void
    {
        $this->fakeDeliveryConfig();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => [
                    'message' => 'Success Create New Order',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => ['order_id' => 9999, 'order_no' => 'RO-ORDER-001'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => [
                    'message' => 'Success Request Pickup',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [[
                    'status' => 'success',
                    'order_no' => 'RO-ORDER-001',
                    'awb' => 'JNE123456789',
                ]],
            ]),
        ]);

        $client = resolve(ShippingDeliveryClient::class);

        $storeResponse = $client->storeOrder(['order_date' => '2026-08-12']);
        $pickupResponse = $client->requestPickup([
            'pickup_date' => '2026-08-13',
            'pickup_time' => '10:00:00',
            'pickup_vehicle' => 'Motor',
            'orders' => [['order_no' => 'RO-ORDER-001']],
        ]);

        $this->assertSame(9999, data_get($storeResponse, 'data.order_id'));
        $this->assertSame('RO-ORDER-001', data_get($storeResponse, 'data.order_no'));
        $this->assertSame('JNE123456789', data_get($pickupResponse, 'data.0.awb'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/orders/store'
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && data_get($request->data(), 'order_date') === '2026-08-12';
        });

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/pickup/request'
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && data_get($request->data(), 'orders.0.order_no') === 'RO-ORDER-001'
                && data_get($request->data(), 'pickup_time') === '10:00:00';
        });
    }

    public function test_delivery_client_tracks_with_shipping_and_airway_bill_query_params(): void
    {
        $this->fakeDeliveryConfig();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['last_status' => 'ON_PROCESS', 'history' => []],
            ]),
        ]);

        resolve(ShippingDeliveryClient::class)->track('JNE123', 'JNE');

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://delivery.example.test/order/api/v1/orders/history-airway-bill')
                && $request['shipping'] === 'JNE'
                && $request['airway_bill'] === 'JNE123'
                && ! array_key_exists('awb', $request->data());
        });
    }

    public function test_delivery_job_creates_order_requests_pickup_and_stores_tracking(): void
    {
        $this->fakeDeliveryConfig();

        [$order, $shipment] = $this->createShipmentReadyForDelivery();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => [
                    'message' => 'Success Create New Order',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => ['order_id' => 9999, 'order_no' => 'RO-ORDER-001'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => [
                    'message' => 'Success Request Pickup',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [[
                    'status' => 'success',
                    'order_no' => 'RO-ORDER-001',
                    'awb' => 'JNE123456789',
                ]],
            ]),
        ]);

        resolve(CreateRajaOngkirDeliveryForShipment::class, [
            'orderShipmentId' => $shipment->id,
        ])->handle(resolve(ShippingDeliveryClient::class));

        $shipment->refresh();
        $this->assertSame('JNE123456789', $shipment->awb);
        $this->assertSame('JNE123456789', $shipment->tracking_number);
        $this->assertSame('labeled', $shipment->status);
        $this->assertSame('RO-ORDER-001', data_get($shipment->metadata, 'komerce.order_no'));

        $order->refresh();
        $this->assertSame(\Shopper\Core\Enum\ShippingStatus::Shipped, $order->shipping_status);

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/orders/store'
                && data_get($payload, 'shipper_destination_id') === 501
                && data_get($payload, 'receiver_destination_id') === 152
                && data_get($payload, 'payment_method') === 'BANK TRANSFER'
                && data_get($payload, 'service_fee') === 0
                && data_get($payload, 'shipping_cashback') === 4500
                && data_get($payload, 'shipping') === 'JNE'
                && data_get($payload, 'shipping_type') === 'REG23'
                && data_get($payload, 'receiver_name') === 'Budi Santoso'
                && data_get($payload, 'receiver_phone') === '081234567890'
                && data_get($payload, 'receiver_address') === 'Jl. Merdeka 1, Jakarta, 10110'
                && data_get($payload, 'order_details.0.product_name') === 'Kopi Cirebon'
                && data_get($payload, 'order_details.0.qty') === 2
                && data_get($payload, 'order_details.0.product_weight') === 500
                && data_get($payload, 'order_details.0.product_width') === 12
                && data_get($payload, 'order_details.0.product_height') === 8
                && data_get($payload, 'order_details.0.product_length') === 20
                && ! array_key_exists('order_no', $payload)
                && ! array_key_exists('origin_id', $payload);
        });

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/pickup/request'
                && data_get($payload, 'orders.0.order_no') === 'RO-ORDER-001'
                && data_get($payload, 'pickup_vehicle') === 'Motor'
                && data_get($payload, 'pickup_time') === '10:00:00'
                && isset($payload['pickup_date'], $payload['pickup_time'])
                && ! array_key_exists('order_no', $payload);
        });
    }

    public function test_delivery_job_persists_komerce_order_metadata_before_pickup(): void
    {
        $this->fakeDeliveryConfig();

        [, $shipment] = $this->createShipmentReadyForDelivery();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => [
                    'message' => 'Success Create New Order',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => ['order_id' => 10001, 'order_no' => 'RO-ORDER-BEFORE-PICKUP'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'success' => false,
            ], 500),
        ]);

        try {
            resolve(CreateRajaOngkirDeliveryForShipment::class, [
                'orderShipmentId' => $shipment->id,
            ])->handle(resolve(ShippingDeliveryClient::class));

            $this->fail('Expected pickup request failure.');
        } catch (RequestException) {
            $shipment->refresh();
        }

        $this->assertSame('10001', data_get($shipment->metadata, 'komerce.order_id'));
        $this->assertSame('RO-ORDER-BEFORE-PICKUP', data_get($shipment->metadata, 'komerce.order_no'));
        $this->assertNull(data_get($shipment->metadata, 'komerce.awb'));
        $this->assertNull($shipment->awb);
    }

    public function test_delivery_job_retry_uses_existing_komerce_order_no_without_storing_again(): void
    {
        $this->fakeDeliveryConfig();

        [, $shipment] = $this->createShipmentReadyForDelivery([
            'metadata' => [
                'komerce' => [
                    'order_id' => 10002,
                    'order_no' => 'RO-ORDER-RETRY',
                    'store_order_response' => [
                        'meta' => [
                            'message' => 'Success Create New Order',
                            'code' => 201,
                            'status' => 'success',
                        ],
                        'data' => [
                            'order_id' => 10002,
                            'order_no' => 'RO-ORDER-RETRY',
                        ],
                    ],
                ],
            ],
        ]);

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => ['code' => 201, 'status' => 'success'],
                'data' => ['order_id' => 99999, 'order_no' => 'RO-SHOULD-NOT-BE-USED'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => [
                    'message' => 'Success Request Pickup',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [[
                    'status' => 'success',
                    'order_no' => 'RO-ORDER-RETRY',
                    'awb' => 'JNE-RETRY-AWB',
                ]],
            ]),
        ]);

        resolve(CreateRajaOngkirDeliveryForShipment::class, [
            'orderShipmentId' => $shipment->id,
        ])->handle(resolve(ShippingDeliveryClient::class));

        $shipment->refresh();
        $this->assertSame('JNE-RETRY-AWB', $shipment->awb);
        $this->assertSame('JNE-RETRY-AWB', $shipment->tracking_number);
        $this->assertSame('RO-ORDER-RETRY', data_get($shipment->metadata, 'komerce.order_no'));

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/orders/store';
        });

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/pickup/request'
                && data_get($payload, 'orders.0.order_no') === 'RO-ORDER-RETRY'
                && data_get($payload, 'pickup_vehicle') === 'Motor'
                && isset($payload['pickup_date'], $payload['pickup_time']);
        });
    }

    public function test_delivery_job_rejects_failed_pickup_item_from_successful_http_response(): void
    {
        $this->fakeDeliveryConfig();

        [, $shipment] = $this->createShipmentReadyForDelivery();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => [
                    'message' => 'Success Create New Order',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => ['order_id' => 10003, 'order_no' => 'RO-PICKUP-FAILED'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => [
                    'message' => 'Success Request Pickup',
                    'code' => 201,
                    'status' => 'success',
                ],
                'data' => [[
                    'status' => 'failed',
                    'order_no' => 'RO-PICKUP-FAILED',
                    'awb' => '',
                ]],
            ]),
        ]);

        try {
            resolve(CreateRajaOngkirDeliveryForShipment::class, [
                'orderShipmentId' => $shipment->id,
            ])->handle(resolve(ShippingDeliveryClient::class));

            $this->fail('Expected the failed pickup item to reject the delivery workflow.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('pickup failed', strtolower($exception->getMessage()));
        }

        $shipment->refresh();
        $this->assertSame('RO-PICKUP-FAILED', data_get($shipment->metadata, 'komerce.order_no'));
        $this->assertNull($shipment->awb);
        $this->assertNull($shipment->tracking_number);
        $this->assertSame('pending', $shipment->status);
    }

    public function test_delivery_job_skips_shipments_that_already_have_tracking(): void
    {
        $this->fakeDeliveryConfig();

        [, $shipment] = $this->createShipmentReadyForDelivery([
            'awb' => 'EXISTING-AWB',
            'tracking_number' => 'EXISTING-AWB',
        ]);

        Http::fake();

        resolve(CreateRajaOngkirDeliveryForShipment::class, [
            'orderShipmentId' => $shipment->id,
        ])->handle(resolve(ShippingDeliveryClient::class));

        Http::assertNothingSent();
        $this->assertSame('EXISTING-AWB', $shipment->refresh()->awb);
    }

    public function test_paid_komerce_transition_dispatches_delivery_job_per_shipment(): void
    {
        $this->fakeDeliveryConfig();
        Bus::fake();

        [$order, $firstShipment] = $this->createShipmentReadyForDelivery();
        $secondShipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $firstShipment->inventory_id,
            'carrier_code' => 'jnt',
            'service_code' => 'EZ',
            'status' => 'pending',
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'type' => TransactionType::Initiate,
            'status' => TransactionStatus::Pending,
            'amount' => 100000,
            'currency_code' => 'IDR',
            'reference' => 'KOMPAY-1001',
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-1001' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-1001',
                    'status' => 'PAID',
                    'amount' => 100000,
                ],
            ]),
        ]);

        $status = resolve(MarkOrderPaidFromKomerce::class)->handle('KOMPAY-1001');

        $this->assertSame('handled', $status);
        Bus::assertDispatched(CreateRajaOngkirDeliveryForShipment::class, 2);
        Bus::assertDispatched(
            CreateRajaOngkirDeliveryForShipment::class,
            fn (CreateRajaOngkirDeliveryForShipment $job): bool => $job->orderShipmentId === $firstShipment->id,
        );
        Bus::assertDispatched(
            CreateRajaOngkirDeliveryForShipment::class,
            fn (CreateRajaOngkirDeliveryForShipment $job): bool => $job->orderShipmentId === $secondShipment->id,
        );
    }

    public function test_already_processed_still_dispatches_delivery_jobs_for_unlabeled_shipments(): void
    {
        $this->fakeDeliveryConfig();

        [$order, $unlabeledShipment] = $this->createShipmentReadyForDelivery();

        // A second shipment that already has tracking — should NOT be re-dispatched
        $labeledShipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $unlabeledShipment->inventory_id,
            'carrier_code' => 'jnt',
            'service_code' => 'EZ',
            'status' => 'labeled',
            'awb' => 'EXISTING-AWB',
            'tracking_number' => 'EXISTING-TRK',
        ]);

        // Mark order as already Paid
        $order->update(['payment_status' => PaymentStatus::Paid]);

        Bus::fake();

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'type' => TransactionType::Capture,
            'status' => TransactionStatus::Success,
            'amount' => 100000,
            'currency_code' => 'IDR',
            'reference' => 'KOMPAY-ALREADY',
        ]);

        // No remote HTTP call needed since we return 'already_processed' before fetching remote status
        $status = resolve(MarkOrderPaidFromKomerce::class)->handle('KOMPAY-ALREADY');

        $this->assertSame('already_processed', $status);

        // Only the unlabeled shipment should be dispatched
        Bus::assertDispatched(
            CreateRajaOngkirDeliveryForShipment::class,
            1,
        );
        Bus::assertDispatched(
            CreateRajaOngkirDeliveryForShipment::class,
            fn (CreateRajaOngkirDeliveryForShipment $job): bool => $job->orderShipmentId === $unlabeledShipment->id,
        );
        Bus::assertNotDispatched(
            CreateRajaOngkirDeliveryForShipment::class,
            fn (CreateRajaOngkirDeliveryForShipment $job): bool => $job->orderShipmentId === $labeledShipment->id,
        );
    }

    /**
     * @param  array<string, mixed>  $shipmentOverrides
     * @return array{0: Order, 1: OrderShipment}
     */
    private function createShipmentReadyForDelivery(array $shipmentOverrides = []): array
    {
        $address = OrderAddress::query()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Merdeka 1',
            'postal_code' => '10110',
            'city' => 'Jakarta',
            'phone' => '081234567890',
        ]);

        $order = Order::factory()->create([
            'number' => 'ORDER-1001',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'shipping_address_id' => $address->id,
            'metadata' => json_encode([
                'komerce' => [
                    'payment_type' => 'bank_transfer',
                ],
                'shipping_address' => [
                    'country_id' => 1,
                    'rajaongkir_destination_id' => '152',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $inventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'email' => 'gudang@oceanmall.test',
            'phone_number' => '02311234567',
            'street_address' => 'Jl. Gudang 10',
            'street_address_plus' => null,
            'city' => 'Cirebon',
            'postal_code' => '45111',
            'rajaongkir_origin_id' => '501',
        ]);

        $shipment = OrderShipment::query()->create(array_merge([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Reguler',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'pending',
            'metadata' => [
                'rate' => [
                    'provider' => 'shipping_delivery',
                    'shipping_name' => 'JNE',
                    'service_name' => 'REG23',
                    'shipping_cost' => 18000,
                    'shipping_cashback' => 4500,
                    'service_fee' => 0,
                    'additional_cost' => 0,
                    'grandtotal' => 118000,
                    'cod_value' => 0,
                    'insurance_value' => 0,
                ],
            ],
        ], $shipmentOverrides));

        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'sku' => 'KOPI-CIREBON-500',
            'weight_value' => 500,
            'weight_unit' => 'g',
            'width_value' => 12,
            'width_unit' => 'cm',
            'height_value' => 8,
            'height_unit' => 'cm',
            'depth_value' => 20,
            'depth_unit' => 'cm',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'product_type' => $product->getMorphClass(),
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price_amount' => 50000,
        ]);

        $shipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 2,
        ]);

        return [$order, $shipment];
    }

    public function test_delivery_job_fails_safely_when_official_tariff_data_is_unavailable(): void
    {
        $this->fakeDeliveryConfig();

        // Create shipment without persisted rate in metadata
        [, $shipment] = $this->createShipmentReadyForDelivery([
            'metadata' => null,
        ]);

        Http::fake([
            'https://delivery.example.test/tariff/api/v1/calculate*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'calculate_reguler' => [],
                    'calculate_cargo' => [],
                    'calculate_instant' => [],
                ],
            ]),
        ]);

        try {
            resolve(CreateRajaOngkirDeliveryForShipment::class, [
                'orderShipmentId' => $shipment->id,
            ])->handle(resolve(ShippingDeliveryClient::class));

            $this->fail('Expected delivery job to fail safely when official tariff data is unavailable.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Data tarif resmi Shipping Delivery belum tersedia', $e->getMessage());
        }

        $shipment->refresh();
        $this->assertSame('pending', $shipment->status);
        $this->assertNull($shipment->awb);

        Http::assertNotSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/orders/store';
        });
    }

    public function test_delivery_job_attempts_dynamic_official_tariff_resolution_when_metadata_rate_is_missing(): void
    {
        $this->fakeDeliveryConfig();

        [, $shipment] = $this->createShipmentReadyForDelivery([
            'metadata' => null,
        ]);

        Http::fake([
            'https://delivery.example.test/tariff/api/v1/calculate*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'calculate_reguler' => [
                        [
                            'shipping_name' => 'JNE',
                            'service_name' => 'REG',
                            'shipping_cost' => 18000,
                            'shipping_cashback' => 4500,
                            'service_fee' => 0,
                            'additional_cost' => 0,
                            'grandtotal' => 118000,
                            'cod_value' => 0,
                            'insurance_value' => 0,
                        ],
                    ],
                ],
            ]),
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => ['message' => 'Success Create New Order', 'code' => 201, 'status' => 'success'],
                'data' => ['order_id' => 8888, 'order_no' => 'RO-DYNAMIC-001'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => ['message' => 'Success Request Pickup', 'code' => 201, 'status' => 'success'],
                'data' => [[
                    'status' => 'success',
                    'order_no' => 'RO-DYNAMIC-001',
                    'awb' => 'JNE-DYNAMIC-AWB',
                ]],
            ]),
        ]);

        resolve(CreateRajaOngkirDeliveryForShipment::class, [
            'orderShipmentId' => $shipment->id,
        ])->handle(resolve(ShippingDeliveryClient::class));

        $shipment->refresh();
        $this->assertSame('JNE-DYNAMIC-AWB', $shipment->awb);
        $this->assertSame('labeled', $shipment->status);
        $this->assertSame('RO-DYNAMIC-001', data_get($shipment->metadata, 'komerce.order_no'));
    }
}
