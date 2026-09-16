<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Livewire\Shopper\KomerceOrderShipping;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Livewire\Livewire;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderShipping;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Zone;
use Spatie\Permission\Models\Role;
use Tests\Support\SignsKomercePaymentCallbacks;
use Tests\TestCase;

/**
 * Vue storefront multi-warehouse checkout → Komerce payment webhook →
 * one RajaOngkir AWB per OrderShipment → delivery webhook → tracking
 * on the same contract as API/Expo and Shopper.
 */
final class MultiShipmentFulfillmentWebTest extends TestCase
{
    use RefreshDatabase;
    use SignsKomercePaymentCallbacks;

    /** @var array{0: Inventory, 1: Inventory} */
    private array $inventories;

    protected function setUp(): void
    {
        parent::setUp();

        $manifest = public_path('build/manifest.json');
        $this->withHeader(
            'X-Inertia-Version',
            file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        );
    }

    public function test_payment_webhook_issues_one_awb_per_shipment_for_a_vue_multi_warehouse_order(): void
    {
        [$user, $paymentMethod] = $this->seedSplitFulfillmentScene();

        $this->placeVueSplitOrder($user, $paymentMethod);

        $order = Order::query()->where('customer_id', $user->id)->latest('id')->firstOrFail();
        $this->assertSame(234000, (int) $order->price_amount);
        $this->assertCount(2, OrderShipment::query()->where('order_id', $order->id)->get());

        $this->postSignedKomercePaymentWebhook([
            'payment_id' => 'KPAY-SPLIT-WEB',
            'order_id' => $order->number,
            'status' => 'PAID',
            'amount' => 234000,
        ])
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);

        [$jakarta, $cirebon] = $this->inventories;
        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->get()
            ->keyBy('inventory_id');

        $jakartaShipment = $shipments->get($jakarta->id);
        $this->assertNotNull($jakartaShipment);
        $this->assertSame('AWB-RO-JNE-1', $jakartaShipment->awb);
        $this->assertSame('AWB-RO-JNE-1', $jakartaShipment->tracking_number);
        $this->assertSame('labeled', $jakartaShipment->status);
        $this->assertSame(18000, (int) $jakartaShipment->cost);

        $cirebonShipment = $shipments->get($cirebon->id);
        $this->assertNotNull($cirebonShipment);
        $this->assertSame('AWB-RO-JNT-1', $cirebonShipment->awb);
        $this->assertSame('AWB-RO-JNT-1', $cirebonShipment->tracking_number);
        $this->assertSame('labeled', $cirebonShipment->status);
        $this->assertSame(16000, (int) $cirebonShipment->cost);

        $this->assertSame(ShippingStatus::Shipped, $order->shipping_status);
        $this->assertDatabaseHas((new OrderShipping)->getTable(), [
            'order_id' => $order->id,
            'tracking_number' => 'AWB-RO-JNE-1',
        ]);

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-JNE-1',
            'cnote' => 'AWB-RO-JNE-1',
            'status' => 'ON_PROCESS',
        ])->assertOk()->assertJson(['status' => 'handled']);

        $this->withoutVite();
        $this->actingAs($user)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('account/order-show')
                ->where('order.payment_status', 'paid')
                ->where('shipments.0.awb', 'AWB-RO-JNE-1')
                ->where('shipments.0.tracking_history.0.description', 'Kurir menuju alamat')
                ->where('shipments.0.tracking_history.0.datetime', '2026-08-16 09:15:00'));

        $this->assertShopperSeesTracking($order, 'Kurir menuju alamat');
    }

    public function test_vue_checkout_success_reconciles_payment_and_issues_awb_without_inbound_webhook(): void
    {
        [$user, $paymentMethod] = $this->seedSplitFulfillmentScene();

        $order = $this->placeVueSplitOrder($user, $paymentMethod);
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);

        $this->withoutVite();
        $this->actingAs($user)
            ->get(route('shop.checkout.success', ['order' => $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('shop/checkout-success')
                ->where('order.payment_status', 'paid')
                ->where('komercePayment', null));

        $this->assertSame(PaymentStatus::Paid, $order->refresh()->payment_status);
        $this->assertCount(2, OrderShipment::query()->where('order_id', $order->id)->whereNotNull('awb')->get());
    }

    private function placeVueSplitOrder(User $user, PaymentMethod $paymentMethod): Order
    {
        [$jakarta, $cirebon] = $this->inventories;
        $cart = Cart::query()->where('customer_id', $user->id)->latest('id')->firstOrFail();

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'zone_country_code' => 'ID',
            ])
            ->post(route('shop.checkout.shipping-address'), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Melawai 1',
                'postal_code' => '12220',
                'city' => 'Jakarta Selatan',
                'state' => 'DKI Jakarta',
                'phone_number' => '081234567890',
                'rajaongkir_destination_id' => '17547',
                'rajaongkir_pin_point' => '-6.2380,106.7830',
            ])
            ->assertRedirect(route('shop.checkout.index'));

        $this->actingAs($user)
            ->post(route('shop.checkout.shipping-option'), [
                'rates' => [
                    (string) $jakarta->id => 'jne:REG',
                    (string) $cirebon->id => 'jnt:EZ',
                ],
            ])
            ->assertRedirect(route('shop.checkout.index'));

        $this->actingAs($user)
            ->post(route('shop.checkout.place-order'), [
                'payment_method_id' => $paymentMethod->id,
            ])
            ->assertRedirect();

        return Order::query()->where('customer_id', $user->id)->latest('id')->firstOrFail();
    }

    /**
     * @return array{0: User, 1: PaymentMethod}
     */
    private function seedSplitFulfillmentScene(): array
    {
        config()->set('komerce.shipping_cost_api_key', 'test-cost-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        config()->set('komerce.pickup_time', '10:00:00');
        config()->set('komerce.pickup_vehicle', 'Motor');

        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $paymentMethod = PaymentMethod::factory()->create([
            'title' => 'BCA Virtual Account',
            'driver' => 'komerce',
            'is_enabled' => true,
            'metadata' => json_encode([
                'channel_code' => 'BCA',
                'payment_type' => 'bank_transfer',
            ]),
        ]);
        $zone->paymentMethods()->attach($paymentMethod->id);

        $jakarta = Inventory::factory()->create([
            'name' => 'Gudang Jakarta',
            'email' => 'jakarta@oceanmall.test',
            'phone_number' => '02112345678',
            'street_address' => 'Jl. Gudang Jakarta 1',
            'street_address_plus' => null,
            'city' => 'Jakarta',
            'postal_code' => '10110',
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'latitude' => '-6.1751',
            'longitude' => '106.8650',
            'country_id' => $country->id,
        ]);
        $cirebon = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'email' => 'cirebon@oceanmall.test',
            'phone_number' => '02311234567',
            'street_address' => 'Jl. Gudang Cirebon 10',
            'street_address_plus' => null,
            'city' => 'Cirebon',
            'postal_code' => '45111',
            'is_default' => false,
            'rajaongkir_origin_id' => '114',
            'latitude' => '-6.7366',
            'longitude' => '108.5414',
            'country_id' => $country->id,
        ]);

        /** @var Product $product */
        $product = Product::factory()->standard()->create([
            'name' => 'Split Fulfillment Product',
            'sku' => 'SPLIT-FULFILL-WEB-1',
            'published_at' => now()->subDay(),
            'weight_value' => 100,
            'weight_unit' => 'g',
            'width_value' => 10,
            'width_unit' => 'cm',
            'height_value' => 6,
            'height_unit' => 'cm',
            'depth_value' => 15,
            'depth_unit' => 'cm',
        ]);
        $product->mutateStock($jakarta->id, 1);
        $product->mutateStock($cirebon->id, 1);

        $user = User::factory()->create();
        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 2,
            'unit_price_amount' => 100000,
        ]);

        $this->inventories = [$jakarta, $cirebon];
        $this->fakeKomerceEndpoints();

        return [$user, $paymentMethod];
    }

    private function fakeKomerceEndpoints(): void
    {
        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    ['name' => 'Jalur Nugraha Ekakurir (JNE)', 'code' => 'jne', 'service' => 'REG', 'cost' => 18000, 'etd' => '2-3'],
                    ['name' => 'J&T Express', 'code' => 'jnt', 'service' => 'EZ', 'cost' => 16000, 'etd' => '3'],
                ],
            ]),
            'https://payment.example.test/user/api/v1/user/methods' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [[
                    'payment_type' => 'va',
                    'bank_code' => 'BCA',
                    'min_amount' => 10000,
                    'max_amount' => 999999999,
                ]],
            ]),
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'payment_id' => 'KPAY-SPLIT-WEB',
                    'va_number' => '1234567890',
                    'bank_code' => 'BCA',
                    'amount' => 234000,
                    'status' => 'PENDING',
                ],
            ]),
            'https://payment.example.test/user/api/v1/user/payment/status/KPAY-SPLIT-WEB' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KPAY-SPLIT-WEB',
                    'status' => 'PAID',
                    'amount' => 234000,
                ],
            ]),
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
                            'grandtotal' => 118000,
                        ],
                        [
                            'shipping_name' => 'JNT',
                            'service_name' => 'EZ',
                            'shipping_cost' => 16000,
                            'shipping_cashback' => 4000,
                            'service_fee' => 0,
                            'grandtotal' => 116000,
                        ],
                    ],
                    'calculate_cargo' => [],
                    'calculate_instant' => [],
                ],
            ]),
            'https://delivery.example.test/order/api/v1/orders/store' => function (Request $request) {
                $courier = (string) data_get($request->data(), 'shipping');
                $orderNo = 'RO-'.($courier === 'JNE' ? 'JNE' : 'JNT').'-1';

                return Http::response([
                    'meta' => ['message' => 'Success Create New Order', 'code' => 201, 'status' => 'success'],
                    'data' => [
                        'order_id' => $courier === 'JNE' ? 61001 : 61002,
                        'order_no' => $orderNo,
                    ],
                ]);
            },
            'https://delivery.example.test/order/api/v1/pickup/request' => function (Request $request) {
                $orderNo = (string) data_get($request->data(), 'orders.0.order_no');

                return Http::response([
                    'meta' => ['message' => 'Success Request Pickup', 'code' => 201, 'status' => 'success'],
                    'data' => [[
                        'status' => 'success',
                        'order_no' => $orderNo,
                        'awb' => 'AWB-'.$orderNo,
                    ]],
                ]);
            },
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success', 'message' => 'Generate Print Label Success'],
                'data' => ['path' => 'https://delivery.example.test/storage/label/split-web.pdf'],
            ]),
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => function (Request $request) {
                $awb = (string) ($request['airway_bill'] ?? '');

                return Http::response([
                    'meta' => ['code' => 200, 'status' => 'success'],
                    'data' => [
                        'airway_bill' => $awb !== '' ? $awb : 'AWB-RO-JNE-1',
                        'last_status' => 'ON_PROCESS',
                        'history' => [[
                            'desc' => 'Kurir menuju alamat',
                            'date' => '2026-08-16 09:15:00',
                            'code' => '100',
                            'status' => 'ON_PROCESS',
                        ]],
                    ],
                ]);
            },
        ]);
    }

    private function assertShopperSeesTracking(Order $order, string $event): void
    {
        $this->configureShopperCpanel();
        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        Livewire::actingAs($admin)
            ->test(KomerceOrderShipping::class, ['order' => $order->fresh()])
            ->assertSee('Riwayat lacak')
            ->assertSee($event);
    }
}
