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
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\TestCase;

final class ShippingDeliveryTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeliveryConfig(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    public function test_delivery_client_posts_store_order_and_pickup_request(): void
    {
        $this->fakeDeliveryConfig();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'success' => true,
                'data' => ['order_no' => 'RO-ORDER-001', 'awb' => 'JNE123456789'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'success' => true,
                'data' => ['pickup_code' => 'PICKUP-001'],
            ]),
        ]);

        $client = resolve(ShippingDeliveryClient::class);

        $storeResponse = $client->storeOrder(['order_no' => 'ORDER-1001-SHIP-1']);
        $pickupResponse = $client->requestPickup(['order_no' => 'RO-ORDER-001']);

        $this->assertSame('RO-ORDER-001', data_get($storeResponse, 'data.order_no'));
        $this->assertSame('PICKUP-001', data_get($pickupResponse, 'data.pickup_code'));

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/orders/store'
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && data_get($request->data(), 'order_no') === 'ORDER-1001-SHIP-1';
        });

        Http::assertSent(function (Request $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/pickup/request'
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && data_get($request->data(), 'order_no') === 'RO-ORDER-001';
        });
    }

    public function test_delivery_client_tracks_with_shipping_and_airway_bill_query_params(): void
    {
        $this->fakeDeliveryConfig();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200],
                'data' => ['status' => 'ON_PROCESS'],
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
                'success' => true,
                'data' => [
                    'order_no' => 'RO-ORDER-001',
                    'awb' => 'JNE123456789',
                    'tracking_number' => 'JNE123456789',
                ],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'success' => true,
                'data' => ['pickup_code' => 'PICKUP-001'],
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
                && data_get($payload, 'shipping') === 'JNE'
                && data_get($payload, 'shipping_type') === 'REG'
                && data_get($payload, 'receiver_name') === 'Budi Santoso'
                && data_get($payload, 'receiver_phone') === '081234567890'
                && data_get($payload, 'receiver_address') === 'Jl. Merdeka 1'
                && data_get($payload, 'order_details.0.product_name') === 'Kopi Cirebon'
                && data_get($payload, 'order_details.0.qty') === 2
                && data_get($payload, 'order_details.0.product_weight') === 500
                && ! array_key_exists('order_no', $payload)
                && ! array_key_exists('origin_id', $payload);
        });

        Http::assertSent(function (Request $request): bool {
            $payload = $request->data();

            return $request->method() === 'POST'
                && $request->url() === 'https://delivery.example.test/order/api/v1/pickup/request'
                && data_get($payload, 'orders.0.order_no') === 'RO-ORDER-001'
                && data_get($payload, 'pickup_vehicle') === 'Motor'
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
                'success' => true,
                'data' => [
                    'order_no' => 'RO-ORDER-BEFORE-PICKUP',
                    'awb' => 'JNE-BEFORE-PICKUP',
                ],
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

        $this->assertSame('RO-ORDER-BEFORE-PICKUP', data_get($shipment->metadata, 'komerce.order_no'));
        $this->assertSame('JNE-BEFORE-PICKUP', data_get($shipment->metadata, 'komerce.awb'));
        $this->assertNull($shipment->awb);
    }

    public function test_delivery_job_retry_uses_existing_komerce_order_no_without_storing_again(): void
    {
        $this->fakeDeliveryConfig();

        [, $shipment] = $this->createShipmentReadyForDelivery([
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-ORDER-RETRY',
                    'awb' => 'JNE-RETRY-AWB',
                    'store_order_response' => [
                        'success' => true,
                        'data' => [
                            'order_no' => 'RO-ORDER-RETRY',
                            'awb' => 'JNE-RETRY-AWB',
                        ],
                    ],
                ],
            ],
        ]);

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'success' => true,
                'data' => ['order_no' => 'RO-SHOULD-NOT-BE-USED'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'success' => true,
                'data' => ['pickup_code' => 'PICKUP-RETRY'],
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
                'shipping_address' => [
                    'country_id' => 1,
                    'rajaongkir_destination_id' => '152',
                ],
            ], JSON_THROW_ON_ERROR),
        ]);

        $inventory = Inventory::factory()->create([
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
        ], $shipmentOverrides));

        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'weight_value' => 500,
            'weight_unit' => 'g',
        ]);

        $shipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 2,
        ]);

        return [$order, $shipment];
    }
}
