<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\CreateKomercePayment;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\CreateOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Shopper\Cart\CartManager;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Product;
use Shopper\Core\Models\Zone;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Tests\Support\SignsKomercePaymentCallbacks;
use Tests\TestCase;

final class KomerceCheckoutTest extends TestCase
{
    use RefreshDatabase;
    use SignsKomercePaymentCallbacks;

    protected function setUp(): void
    {
        parent::setUp();

        // Raw X-Inertia requests get a 409 (asset-version mismatch) once a Vite
        // manifest exists, because they carry no version. Send the version the
        // Inertia middleware computes so these requests are accepted.
        $manifest = public_path('build/manifest.json');
        $this->withHeader(
            'X-Inertia-Version',
            file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        );
    }

    private function komerceFakeConfig(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');
        config()->set('shopper.payment.drivers.stripe.enabled', false);
    }

    private function seedCountryZoneAndPaymentMethod(string $driver = 'komerce', array $metadata = []): array
    {
        $country = Country::factory()->create();
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $effectiveMeta = $metadata ?: ['channel_code' => 'BCA', 'payment_type' => 'bank_transfer'];

        $paymentMethod = PaymentMethod::factory()->create([
            'title' => 'BCA Virtual Account',
            'driver' => $driver,
            'is_enabled' => true,
            'metadata' => json_encode($effectiveMeta),
        ]);
        $zone->paymentMethods()->attach($paymentMethod->id);

        return [$country, $zone, $paymentMethod];
    }

    private function makeCheckoutSession(int $countryId, int $paymentMethodId, array $extra = []): array
    {
        return [
            'shipping_address' => [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Merdeka 1',
                'postal_code' => '10110',
                'city' => 'Jakarta',
                'country_id' => $countryId,
                'phone_number' => '081234567890',
            ],
            'shipping_option' => [
                [
                    'id' => 'JNE-REG',
                    'name' => 'JNE Reguler',
                    'price' => 15000,
                    'service_code' => 'JNE-REG',
                    'carrier_code' => 'JNE',
                    'currency' => 'IDR',
                    'estimated_days' => 3,
                ],
            ],
            'payment' => [
                array_merge([
                    'id' => $paymentMethodId,
                    'driver' => 'komerce',
                    'channel_code' => 'BCA',
                    'payment_type' => 'bank_transfer',
                    'title' => 'BCA Virtual Account',
                ], $extra),
            ],
        ];
    }

    public function test_checkout_keeps_komerce_place_order_available_before_payment_instructions(): void
    {
        $checkoutPage = file_get_contents(resource_path('js/pages/shop/checkout.vue'));

        $this->assertIsString($checkoutPage);
        $this->assertStringContainsString('const canPlaceOrder = computed<boolean>', $checkoutPage);
        $this->assertMatchesRegularExpression(
            '/!isKomerceSelected\.value\s*\|\|\s*!props\.komercePayment/s',
            $checkoutPage,
        );
        $this->assertStringContainsString('v-if="canPlaceOrder"', $checkoutPage);
        $this->assertStringContainsString('v-if="isKomerceSelected && komercePayment"', $checkoutPage);
        $this->assertMatchesRegularExpression(
            '/currentPaymentMethod\.value\?\.driver === \'stripe\'\s*,/s',
            $checkoutPage,
        );
    }

    public function test_checkout_renders_selected_komerce_without_payment_instructions(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create();
        [$country, , $paymentMethod] = $this->seedCountryZoneAndPaymentMethod();

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        $product = Product::factory()->standard()->create(['name' => 'Batik Shirt']);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 100000,
        ]);

        $this->app->instance(BuildShippingPackages::class, new class
        {
            public function handle(): array
            {
                return [];
            }
        });
        $this->app->instance(FetchDeliveryRates::class, new class
        {
            public function handle(): array
            {
                return [[
                    'id' => 'JNE-REG',
                    'service_code' => 'JNE-REG',
                    'service_name' => 'JNE Reguler',
                    'carrier_code' => 'JNE',
                    'carrier_name' => 'JNE',
                    'carrier_logo' => null,
                    'description' => null,
                    'estimated_days' => 3,
                    'amount' => 15000,
                    'currency' => 'IDR',
                ]];
            }
        });
        $this->app->instance(CartManager::class, new class
        {
            public function calculate(Cart $cart): array
            {
                return [
                    'total' => 100000,
                    'taxTotal' => 0,
                    'discountTotal' => 0,
                ];
            }
        });

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'checkout' => $this->makeCheckoutSession($country->id, $paymentMethod->id),
            ])
            ->get(route('shop.checkout.index', ['step' => 3]), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertHeader('X-Inertia', 'true')
            ->assertJsonPath('component', 'shop/checkout')
            ->assertJsonPath('props.selectedPaymentMethod', $paymentMethod->id)
            ->assertJsonPath('props.komercePayment', null)
            ->assertJsonPath('props.stripeData', null);
    }

    public function test_place_order_creates_komerce_va_and_pending_transaction(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'email' => 'budi@example.test',
        ]);

        [$country, , $paymentMethod] = $this->seedCountryZoneAndPaymentMethod();

        $order = Order::factory()->create([
            'number' => 'ORD-TEST-001',
            'price_amount' => 150000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        $this->app->instance(CreateOrder::class, new class($order)
        {
            public function __construct(private readonly Order $order) {}

            public function handle(): Order
            {
                return $this->order;
            }
        });

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-VA-001',
                    'virtual_account_number' => '8808001234567890',
                    'bank_code' => 'BCA',
                    'expiry_date' => '2026-08-04 23:59:59',
                    'amount' => 150000,
                ],
            ]),
        ]);

        $session = ['checkout' => $this->makeCheckoutSession($country->id, $paymentMethod->id)];

        $response = $this->actingAs($user)
            ->withSession($session)
            ->post(route('shop.checkout.place-order'), [
                'payment_method_id' => $paymentMethod->id,
            ]);

        $response->assertRedirect(route('shop.checkout.success', ['order' => $order->id]));

        $this->assertDatabaseHas(
            (new PaymentTransaction)->getTable(),
            [
                'order_id' => $order->id,
                'driver' => 'komerce',
                'reference' => 'KOMPAY-VA-001',
                'status' => TransactionStatus::Pending->value,
                'type' => TransactionType::Initiate->value,
            ],
        );

        $order->refresh();
        $this->assertSame(PaymentStatus::Pending, $order->payment_status);

        Http::assertSent(function (ClientRequest $req): bool {
            return str_contains($req->url(), '/api/v1/user/payment/create')
                && $req->method() === 'POST'
                && $req->hasHeader('x-api-key', 'test-komerce-key');
        });
    }

    public function test_place_order_creates_komerce_qris_payment(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create(['first_name' => 'Sari', 'last_name' => 'Wulandari', 'email' => 'sari@example.test']);

        [$country, , $paymentMethod] = $this->seedCountryZoneAndPaymentMethod(
            'komerce',
            ['payment_type' => 'qris'],
        );

        $order = Order::factory()->create([
            'number' => 'ORD-TEST-002',
            'price_amount' => 200000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        $this->app->instance(CreateOrder::class, new class($order)
        {
            public function __construct(private readonly Order $order) {}

            public function handle(): Order
            {
                return $this->order;
            }
        });

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-QRIS-001',
                    'qris_string' => '00020101021226640013...',
                    'expiry_date' => '2026-08-04 23:59:59',
                    'amount' => 200000,
                ],
            ]),
        ]);

        $session = [
            'checkout' => $this->makeCheckoutSession($country->id, $paymentMethod->id, [
                'payment_type' => 'qris',
                'channel_code' => null,
            ]),
        ];

        $response = $this->actingAs($user)
            ->withSession($session)
            ->post(route('shop.checkout.place-order'), [
                'payment_method_id' => $paymentMethod->id,
            ]);

        $response->assertRedirect(route('shop.checkout.success', ['order' => $order->id]));

        $this->assertDatabaseHas(
            (new PaymentTransaction)->getTable(),
            [
                'order_id' => $order->id,
                'driver' => 'komerce',
                'reference' => 'KOMPAY-QRIS-001',
                'status' => TransactionStatus::Pending->value,
            ],
        );
    }

    public function test_place_order_komerce_then_webhook_marks_paid(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create(['first_name' => 'Andi', 'last_name' => 'Prasetyo', 'email' => 'andi@example.test']);

        [$country, , $paymentMethod] = $this->seedCountryZoneAndPaymentMethod();

        $order = Order::factory()->create([
            'number' => 'ORD-TEST-003',
            'price_amount' => 175000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        $this->app->instance(CreateOrder::class, new class($order)
        {
            public function __construct(private readonly Order $order) {}

            public function handle(): Order
            {
                return $this->order;
            }
        });

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-E2E-001',
                    'virtual_account_number' => '8808009876543210',
                    'bank_code' => 'BCA',
                    'expiry_date' => '2026-08-04 23:59:59',
                    'amount' => 175000,
                ],
            ]),
            'https://payment.example.test/user/api/v1/user/payment/status/KOMPAY-E2E-001' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-E2E-001',
                    'status' => 'PAID',
                    'amount' => 175000,
                ],
            ]),
        ]);

        // Step 1: place order
        $this->actingAs($user)
            ->withSession(['checkout' => $this->makeCheckoutSession($country->id, $paymentMethod->id)])
            ->post(route('shop.checkout.place-order'), ['payment_method_id' => $paymentMethod->id])
            ->assertRedirect(route('shop.checkout.success', ['order' => $order->id]));

        $this->assertDatabaseHas(
            (new PaymentTransaction)->getTable(),
            ['order_id' => $order->id, 'reference' => 'KOMPAY-E2E-001', 'status' => TransactionStatus::Pending->value],
        );

        // Step 2: webhook callback marks paid
        $this->postSignedKomercePaymentWebhook([
            'payment_id' => 'KOMPAY-E2E-001',
            'order_id' => 'ORD-TEST-003',
            'status' => 'PAID',
            'amount' => 175000,
        ])
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $order->refresh();
        $this->assertSame(PaymentStatus::Paid, $order->payment_status);
        $this->assertSame(OrderStatus::Processing, $order->status);

        $transaction = PaymentTransaction::query()
            ->where('order_id', $order->id)
            ->where('reference', 'KOMPAY-E2E-001')
            ->firstOrFail();
        $this->assertSame(TransactionStatus::Success, $transaction->status);
        $this->assertSame(TransactionType::Capture, $transaction->type);
    }

    public function test_fetch_payment_methods_excludes_stripe_when_disabled(): void
    {
        $this->komerceFakeConfig();

        $country = Country::factory()->create();
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $komerce = PaymentMethod::factory()->create([
            'driver' => 'komerce',
            'is_enabled' => true,
            'metadata' => json_encode(['channel_code' => 'BCA', 'payment_type' => 'bank_transfer']),
        ]);
        $stripe = PaymentMethod::factory()->create([
            'driver' => 'stripe',
            'is_enabled' => true,
        ]);
        $zone->paymentMethods()->attach([$komerce->id, $stripe->id]);

        $methods = resolve(FetchPaymentMethods::class)->handle($country->id);

        $drivers = array_column($methods, 'driver');
        $this->assertContains('komerce', $drivers);
        $this->assertNotContains('stripe', $drivers);
    }

    public function test_fetch_payment_methods_includes_stripe_when_enabled(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('shopper.payment.drivers.stripe.enabled', true);

        $country = Country::factory()->create();
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $stripe = PaymentMethod::factory()->create([
            'driver' => 'stripe',
            'is_enabled' => true,
        ]);
        $zone->paymentMethods()->attach([$stripe->id]);

        $methods = resolve(FetchPaymentMethods::class)->handle($country->id);
        $drivers = array_column($methods, 'driver');

        $this->assertContains('stripe', $drivers);
    }

    public function test_fetch_payment_methods_excludes_unconfigured_default_drivers(): void
    {
        $this->komerceFakeConfig();

        $country = Country::factory()->create();
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $manual = PaymentMethod::factory()->create([
            'driver' => 'manual',
            'is_enabled' => true,
        ]);
        $paypal = PaymentMethod::factory()->create([
            'driver' => 'paypal',
            'is_enabled' => true,
        ]);
        $zone->paymentMethods()->attach([$manual->id, $paypal->id]);

        $methods = resolve(FetchPaymentMethods::class)->handle($country->id);
        $drivers = array_column($methods, 'driver');

        $this->assertContains('manual', $drivers);
        $this->assertNotContains('paypal', $drivers);
    }

    public function test_place_order_rejects_stripe_driver_with_error(): void
    {
        // Stripe IS enabled in the zone, but placeOrder should reject the stripe path.
        config()->set('shopper.payment.drivers.stripe.enabled', true);
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');

        $user = User::factory()->create();

        $country = Country::factory()->create();
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $stripeMethod = PaymentMethod::factory()->create(['driver' => 'stripe', 'is_enabled' => true]);
        $zone->paymentMethods()->attach($stripeMethod->id);

        $session = [
            'checkout' => [
                'shipping_address' => ['country_id' => $country->id, 'first_name' => 'Test', 'last_name' => 'User', 'street_address' => 'Jl. 1', 'postal_code' => '10110', 'city' => 'Jakarta'],
                'shipping_option' => [['id' => 'JNE-REG', 'name' => 'JNE', 'price' => 15000]],
                'payment' => [['id' => $stripeMethod->id, 'driver' => 'stripe', 'title' => 'Stripe']],
            ],
        ];

        $this->actingAs($user)
            ->withSession($session)
            ->post(route('shop.checkout.place-order'), ['payment_method_id' => $stripeMethod->id])
            ->assertSessionHasErrors('payment');
    }

    public function test_create_komerce_payment_action_builds_va_payload_correctly(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.test']);

        $paymentMethod = PaymentMethod::factory()->create(['driver' => 'komerce', 'is_enabled' => true]);

        $order = Order::factory()->create([
            'number' => 'ORD-ACTION-001',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'success' => true,
                'data' => [
                    'payment_id' => 'KOMPAY-ACTION-001',
                    'virtual_account_number' => '8808001111111111',
                    'bank_code' => 'BCA',
                    'expiry_date' => '2026-08-04 23:59:59',
                    'amount' => 100000,
                ],
            ]),
        ]);

        $instructions = resolve(CreateKomercePayment::class)->handle($order, [
            'id' => $paymentMethod->id,
            'driver' => 'komerce',
            'channel_code' => 'BCA',
            'payment_type' => 'bank_transfer',
        ]);

        $this->assertSame('KOMPAY-ACTION-001', $instructions['payment_id']);
        $this->assertSame('bank_transfer', $instructions['payment_type']);
        $this->assertSame('8808001111111111', $instructions['virtual_account_number']);
        $this->assertSame('BCA', $instructions['bank_code']);

        $this->assertDatabaseHas(
            (new PaymentTransaction)->getTable(),
            [
                'order_id' => $order->id,
                'driver' => 'komerce',
                'reference' => 'KOMPAY-ACTION-001',
                'status' => TransactionStatus::Pending->value,
            ],
        );

        Http::assertSent(function (ClientRequest $req): bool {
            $body = $req->data();

            return str_contains($req->url(), '/api/v1/user/payment/create')
                && $req->method() === 'POST'
                && data_get($body, 'payment_type') === 'bank_transfer'
                && data_get($body, 'channel_code') === 'BCA'
                && data_get($body, 'order_id') === 'ORD-ACTION-001'
                && data_get($body, 'amount') === 100000
                && is_array(data_get($body, 'items'))
                && count(data_get($body, 'items')) >= 1
                && data_get($body, 'items.0.name') !== null
                && data_get($body, 'items.0.quantity') >= 1
                && data_get($body, 'items.0.price') >= 0;
        });
    }

    public function test_create_komerce_payment_maps_collaborator_va_and_qris_field_names(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create(['first_name' => 'Uat', 'last_name' => 'User', 'email' => 'uat-map@example.test']);
        $paymentMethod = PaymentMethod::factory()->create(['driver' => 'komerce', 'is_enabled' => true]);

        $vaOrder = Order::factory()->create([
            'number' => 'ORD-COLLAB-VA',
            'price_amount' => 15000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::sequence()
                ->push([
                    'meta' => ['message' => 'success create payment', 'code' => 200, 'status' => 'success'],
                    'data' => [
                        'payment_id' => 'KPAY-5292/KM/2026',
                        'payment_url' => 'https://pay-sandbox.komerce.my.id/example',
                        'va_number' => '381659999574893',
                        'qr_string' => '',
                        'bank_code' => 'BCA',
                        'amount' => 15000,
                        'status' => 'PENDING',
                        'expired_at' => '2026-08-05T05:04:54.252204638+07:00',
                    ],
                ])
                ->push([
                    'meta' => ['message' => 'success create payment', 'code' => 200, 'status' => 'success'],
                    'data' => [
                        'payment_id' => 'KPAY-b307/KM/2026',
                        'payment_url' => 'https://pay-sandbox.komerce.my.id/qris-example',
                        'va_number' => '',
                        'qr_string' => '00020101021226640013ID.CO.QRIS.WWW',
                        'bank_code' => '',
                        'amount' => 12000,
                        'status' => 'PENDING',
                        'expired_at' => '2026-08-05T04:09:55.141807+07:00',
                    ],
                ]),
        ]);

        $va = resolve(CreateKomercePayment::class)->handle($vaOrder, [
            'driver' => 'komerce',
            'channel_code' => 'BCA',
            'payment_type' => 'bank_transfer',
        ]);

        $this->assertSame('KPAY-5292/KM/2026', $va['payment_id']);
        $this->assertSame('381659999574893', $va['virtual_account_number']);
        $this->assertSame('BCA', $va['bank_code']);
        $this->assertSame('2026-08-05T05:04:54.252204638+07:00', $va['expiry_date']);
        $this->assertSame('https://pay-sandbox.komerce.my.id/example', $va['payment_url']);

        $qrisOrder = Order::factory()->create([
            'number' => 'ORD-COLLAB-QRIS',
            'price_amount' => 12000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        $qris = resolve(CreateKomercePayment::class)->handle($qrisOrder, [
            'driver' => 'komerce',
            'payment_type' => 'qris',
        ]);

        $this->assertSame('KPAY-b307/KM/2026', $qris['payment_id']);
        $this->assertSame('00020101021226640013ID.CO.QRIS.WWW', $qris['qris_string']);
        $this->assertSame('2026-08-05T04:09:55.141807+07:00', $qris['expiry_date']);
        $this->assertSame('https://pay-sandbox.komerce.my.id/qris-example', $qris['payment_url']);
    }

    public function test_place_order_komerce_payment_failure_redirects_to_account_order_with_pending_order(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'fail@example.test']);

        [$country, , $paymentMethod] = $this->seedCountryZoneAndPaymentMethod();

        $order = Order::factory()->create([
            'number' => 'ORD-PAYFAIL-001',
            'price_amount' => 120000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        $this->app->instance(CreateOrder::class, new class($order)
        {
            public function __construct(private readonly Order $order) {}

            public function handle(): Order { return $this->order; }
        });

        // Komerce payment creation fails (e.g. gateway error)
        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'meta' => ['status' => 'error', 'code' => 500, 'message' => 'Gateway unavailable.'],
                'data' => ['payment_id' => ''],
            ], 200),
        ]);

        $session = ['checkout' => $this->makeCheckoutSession($country->id, $paymentMethod->id)];

        $response = $this->actingAs($user)
            ->withSession($session)
            ->post(route('shop.checkout.place-order'), [
                'payment_method_id' => $paymentMethod->id,
            ]);

        // Order exists — send customer to account order so they can retry payment.
        $response->assertRedirect(route('account.orders.show', ['order' => $order->id]));
        $response->assertSessionHas('error');
    }

    public function test_create_komerce_payment_rejects_failed_response_without_payment_id(): void
    {
        $this->komerceFakeConfig();

        $user = User::factory()->create(['first_name' => 'Test', 'last_name' => 'User', 'email' => 'test@example.test']);

        $paymentMethod = PaymentMethod::factory()->create(['driver' => 'komerce', 'is_enabled' => true]);

        $order = Order::factory()->create([
            'number' => 'ORD-ACTION-FAILED',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
            'payment_method_id' => $paymentMethod->id,
            'payment_status' => PaymentStatus::Pending,
            'status' => OrderStatus::New,
        ]);

        Http::fake([
            'https://payment.example.test/user/api/v1/user/payment/create' => Http::response([
                'meta' => [
                    'status' => 'error',
                    'code' => 400,
                    'message' => 'Payment channel is unavailable.',
                ],
                'data' => [
                    'payment_id' => '',
                ],
            ], 200),
        ]);

        try {
            resolve(CreateKomercePayment::class)->handle($order, [
                'id' => $paymentMethod->id,
                'driver' => 'komerce',
                'channel_code' => 'BCA',
                'payment_type' => 'bank_transfer',
            ]);

            $this->fail('Expected failed Komerce response to throw.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Komerce payment creation failed', $e->getMessage());
        }

        $this->assertDatabaseMissing(
            (new PaymentTransaction)->getTable(),
            [
                'order_id' => $order->id,
                'driver' => 'komerce',
                'reference' => '',
            ],
        );
        $this->assertSame(0, PaymentTransaction::query()->where('order_id', $order->id)->count());
    }
}
