<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\CreateOrder;
use App\CheckoutSession;
use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Shopper\Cart\CartManager;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Product;
use Shopper\Core\Models\Zone;
use Tests\TestCase;

final class MultiPackageCheckoutTest extends TestCase
{
    use RefreshDatabase;

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

    private function fakeRajaOngkirConfig(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
        config()->set('komerce.couriers', ['jne', 'jnt']);
    }

    private function fakeDomesticCostResponse(string $originId, int $jneAmount, int $jntAmount): void
    {
        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::sequence()
                ->push([
                    'meta' => ['code' => 200, 'message' => 'Success'],
                    'data' => [
                        [
                            'name' => 'Jalur Nugraha Ekakurir (JNE)',
                            'code' => 'jne',
                            'service' => 'REG',
                            'description' => 'Layanan Reguler',
                            'cost' => $jneAmount,
                            'etd' => '2-3',
                        ],
                        [
                            'name' => 'J&T Express',
                            'code' => 'jnt',
                            'service' => 'EZ',
                            'description' => 'Regular Service',
                            'cost' => $jntAmount,
                            'etd' => '3',
                        ],
                    ],
                ])
                ->push([
                    'meta' => ['code' => 200, 'message' => 'Success'],
                    'data' => [
                        [
                            'name' => 'Jalur Nugraha Ekakurir (JNE)',
                            'code' => 'jne',
                            'service' => 'REG',
                            'description' => 'Layanan Reguler',
                            'cost' => $jneAmount + 5000,
                            'etd' => '3-4',
                        ],
                        [
                            'name' => 'J&T Express',
                            'code' => 'jnt',
                            'service' => 'EZ',
                            'description' => 'Regular Service',
                            'cost' => $jntAmount + 3000,
                            'etd' => '4',
                        ],
                    ],
                ]),
        ]);
    }

    /**
     * Test that checkout index returns allocation and deliveryOptionsByShipment
     * when the cart requires split shipments across two inventories.
     */
    public function test_checkout_index_returns_allocation_and_per_shipment_rates_for_split_cart(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $defaultInventory = Inventory::factory()->create([
            'name' => 'Gudang Jakarta',
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'country_id' => $country->id,
        ]);
        $secondaryInventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'is_default' => false,
            'rajaongkir_origin_id' => '114',
            'country_id' => $country->id,
        ]);

        /** @var Product $product */
        $product = Product::factory()->standard()->create([
            'name' => 'Test Product',
            'weight_value' => 100,
            'weight_unit' => 'g',
        ]);
        // 1 unit each → neither inventory can fulfil qty=2 alone, forcing a split
        $product->mutateStock($defaultInventory->id, 1);
        $product->mutateStock($secondaryInventory->id, 1);

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

        $this->app->instance(BuildShippingPackages::class, new class
        {
            public function handle(): array { return []; }

            public function handleFromLines(array $lines): array { return []; }
        });

        $this->app->instance(CartManager::class, new class
        {
            public function addAddress(Cart $cart): void {}

            public function calculate(Cart $cart): array
            {
                return ['total' => 200000, 'taxTotal' => 0, 'discountTotal' => 0];
            }
        });

        $this->fakeDomesticCostResponse('501', 18000, 16000);

        $response = $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                CheckoutSession::KEY => [
                    'shipping_address' => [
                        'first_name' => 'Budi',
                        'last_name' => 'Santoso',
                        'street_address' => 'Jl. Merdeka 1',
                        'postal_code' => '10110',
                        'city' => 'Jakarta',
                        'country_id' => $country->id,
                        'phone_number' => '081234567890',
                        'rajaongkir_destination_id' => '152',
                    ],
                ],
            ])
            ->get(route('shop.checkout.index', ['step' => 2]), ['X-Inertia' => 'true']);

        $response->assertOk();

        $data = $response->json('props');

        $this->assertNotNull($data['allocation']);
        $this->assertCount(2, $data['allocation']);

        $inventoryIds = array_column($data['allocation'], 'inventory_id');
        $this->assertContains($defaultInventory->id, $inventoryIds);
        $this->assertContains($secondaryInventory->id, $inventoryIds);

        $inventoryNames = array_column($data['allocation'], 'inventory_name');
        $this->assertContains('Gudang Jakarta', $inventoryNames);
        $this->assertContains('Gudang Cirebon', $inventoryNames);

        $this->assertArrayHasKey((string) $defaultInventory->id, $data['deliveryOptionsByShipment']);
        $this->assertArrayHasKey((string) $secondaryInventory->id, $data['deliveryOptionsByShipment']);

        $jktOptions = array_values($data['deliveryOptionsByShipment'][(string) $defaultInventory->id]);
        $this->assertCount(2, $jktOptions);
        $this->assertSame('jne:REG', $jktOptions[0]['service_code']);
        $this->assertSame(18000, $jktOptions[0]['amount']);
    }

    /**
     * Test that saving per-shipment rates stores session map and synthetic global option.
     */
    public function test_save_shipping_option_stores_per_shipment_rates_map(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);

        $defaultInventory = Inventory::factory()->create([
            'name' => 'Gudang Jakarta',
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'country_id' => $country->id,
        ]);
        $secondaryInventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'is_default' => false,
            'rajaongkir_origin_id' => '114',
            'country_id' => $country->id,
        ]);

        /** @var Product $product */
        $product = Product::factory()->standard()->create([
            'name' => 'Split Product',
            'weight_value' => 100,
            'weight_unit' => 'g',
        ]);
        // 1 unit each → neither inventory can fulfil qty=2 alone, forcing a split
        $product->mutateStock($defaultInventory->id, 1);
        $product->mutateStock($secondaryInventory->id, 1);

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

        $this->app->instance(BuildShippingPackages::class, new class
        {
            public function handle(): array { return []; }

            public function handleFromLines(array $lines): array { return []; }
        });

        $shippingAddress = [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Merdeka 1',
            'postal_code' => '10110',
            'city' => 'Jakarta',
            'country_id' => $country->id,
            'rajaongkir_destination_id' => '152',
        ];

        // Two HTTP responses: one per inventory shipment (default → jne:REG @ 12000, secondary → jnt:EZ @ 13000)
        $this->fakeDomesticCostResponse('501', 12000, 10000);

        $response = $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                CheckoutSession::KEY => [
                    'shipping_address' => $shippingAddress,
                ],
            ])
            ->post(route('shop.checkout.shipping-option'), [
                'rates' => [
                    $defaultInventory->id => 'jne:REG',
                    $secondaryInventory->id => 'jnt:EZ',
                ],
            ]);

        $response->assertRedirect(route('shop.checkout.index'));

        $sessionRates = session()->get(CheckoutSession::SHIPPING_OPTIONS_BY_SHIPMENT);
        $this->assertIsArray($sessionRates);
        $this->assertArrayHasKey($defaultInventory->id, $sessionRates);
        $this->assertArrayHasKey($secondaryInventory->id, $sessionRates);

        $globalOption = session()->get(CheckoutSession::SHIPPING_OPTION . '.0');
        $this->assertIsArray($globalOption);
        $this->assertSame('split-shipment', $globalOption['service_code']);
        // jne:REG from first response (12000) + jnt:EZ from second response (10000+3000 = 13000) = 25000
        $this->assertSame(12000 + 13000, $globalOption['price']);
    }

    /**
     * Test that checkout page correctly displays allocation props.
     */
    public function test_checkout_vue_source_contains_shipment_rate_picker_import(): void
    {
        $checkoutPage = file_get_contents(resource_path('js/pages/shop/checkout.vue'));

        $this->assertIsString($checkoutPage);
        $this->assertStringContainsString('ShipmentRatePicker', $checkoutPage);
        $this->assertStringContainsString('shipment-rate-picker.vue', $checkoutPage);
        $this->assertStringContainsString('isMultiPackage', $checkoutPage);
        $this->assertStringContainsString('deliveryOptionsByShipment', $checkoutPage);
        $this->assertStringContainsString('allPackagesSelected', $checkoutPage);
        $this->assertStringContainsString('submitMultiShipping', $checkoutPage);
    }

    /**
     * Test FetchDeliveryRates uses the specified inventory's origin id when provided.
     */
    public function test_fetch_delivery_rates_uses_origin_inventory_id_when_provided(): void
    {
        $this->fakeRajaOngkirConfig();

        $country = Country::factory()->create(['cca2' => 'ID']);

        Inventory::factory()->create([
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'country_id' => $country->id,
        ]);

        $secondInventory = Inventory::factory()->create([
            'is_default' => false,
            'rajaongkir_origin_id' => '999',
            'country_id' => $country->id,
        ]);

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Success'],
                'data' => [
                    [
                        'name' => 'JNE',
                        'code' => 'jne',
                        'service' => 'REG',
                        'cost' => 20000,
                        'etd' => '2-3',
                    ],
                ],
            ]),
        ]);

        $rates = resolve(FetchDeliveryRates::class)->handle(
            ['rajaongkir_destination_id' => '114'],
            [],
            $secondInventory->id,
        );

        $this->assertNotEmpty($rates);
        $this->assertSame('jne:REG', $rates[0]['service_code']);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return (string) $request['origin'] === '999';
        });
    }

    /**
     * Critical: Two shipments with different qtys must send different weights to RajaOngkir.
     */
    public function test_per_shipment_weight_uses_allocated_lines_only(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $inv1 = Inventory::factory()->create([
            'name' => 'Gudang A',
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'country_id' => $country->id,
        ]);
        $inv2 = Inventory::factory()->create([
            'name' => 'Gudang B',
            'is_default' => false,
            'rajaongkir_origin_id' => '114',
            'country_id' => $country->id,
        ]);

        $product = Product::factory()->standard()->create([
            'weight_value' => 1000,
            'weight_unit' => 'g',
        ]);
        // inv1 has qty=1, inv2 has qty=2 → different allocated qtys → different weights
        $product->mutateStock($inv1->id, 1);
        $product->mutateStock($inv2->id, 2);

        $cart = Cart::query()->create(['currency_code' => 'IDR', 'customer_id' => $user->id]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 3,
            'unit_price_amount' => 100000,
        ]);

        $this->app->instance(CartManager::class, new class
        {
            public function addAddress(Cart $cart): void {}

            public function calculate(Cart $cart): array
            {
                return ['total' => 300000, 'taxTotal' => 0, 'discountTotal' => 0];
            }
        });

        // Fake two sequential responses for the two shipment HTTP calls
        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::sequence()
                ->push(['meta' => ['code' => 200], 'data' => [['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 15000, 'etd' => '2']]])
                ->push(['meta' => ['code' => 200], 'data' => [['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 20000, 'etd' => '2']]]),
        ]);

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                CheckoutSession::KEY => [
                    'shipping_address' => [
                        'first_name' => 'Budi',
                        'last_name' => 'S',
                        'street_address' => 'Jl. A 1',
                        'postal_code' => '10110',
                        'city' => 'Jakarta',
                        'country_id' => $country->id,
                        'rajaongkir_destination_id' => '152',
                    ],
                ],
            ])
            ->get(route('shop.checkout.index', ['step' => 2]), ['X-Inertia' => 'true'])
            ->assertOk();

        $recorded = Http::recorded()->values();
        $this->assertCount(2, $recorded, 'Expected exactly 2 RajaOngkir requests (one per shipment)');

        $weights = $recorded->map(fn ($pair) => (int) $pair[0]['weight'])->sort()->values()->all();

        // inv1 ships qty=1 × 1000 g = 1000 g; inv2 ships qty=2 × 1000 g = 2000 g
        $this->assertSame([1000, 2000], $weights, 'Each shipment must send its own allocated weight');
    }

    /**
     * Important: selectedRatesByShipment Inertia prop must be hydrated as service_code strings.
     */
    public function test_inertia_selected_rates_by_shipment_are_service_code_strings(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        $inv1 = Inventory::factory()->create([
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'country_id' => $country->id,
        ]);
        $inv2 = Inventory::factory()->create([
            'is_default' => false,
            'rajaongkir_origin_id' => '114',
            'country_id' => $country->id,
        ]);

        $product = Product::factory()->standard()->create(['weight_value' => 500, 'weight_unit' => 'g']);
        $product->mutateStock($inv1->id, 1);
        $product->mutateStock($inv2->id, 1);

        $cart = Cart::query()->create(['currency_code' => 'IDR', 'customer_id' => $user->id]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 2,
            'unit_price_amount' => 100000,
        ]);

        $this->app->instance(CartManager::class, new class
        {
            public function addAddress(Cart $cart): void {}

            public function calculate(Cart $cart): array
            {
                return ['total' => 200000, 'taxTotal' => 0, 'discountTotal' => 0];
            }
        });

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200],
                'data' => [['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 18000, 'etd' => '2']],
            ]),
        ]);

        // Store stored rates as full arrays (as they are persisted after saveShippingOptionsByShipment)
        $storedRates = [
            $inv1->id => ['service_code' => 'jne:REG', 'amount' => 18000, 'currency' => 'IDR'],
            $inv2->id => ['service_code' => 'jnt:EZ', 'amount' => 13000, 'currency' => 'IDR'],
        ];

        $response = $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                CheckoutSession::KEY => [
                    'shipping_address' => [
                        'first_name' => 'Budi',
                        'last_name' => 'S',
                        'street_address' => 'Jl. A 1',
                        'postal_code' => '10110',
                        'city' => 'Jakarta',
                        'country_id' => $country->id,
                        'rajaongkir_destination_id' => '152',
                    ],
                    'shipping_options_by_shipment' => $storedRates,
                ],
            ])
            ->get(route('shop.checkout.index', ['step' => 2]), ['X-Inertia' => 'true'])
            ->assertOk();

        $props = $response->json('props');
        $selected = $props['selectedRatesByShipment'];

        $this->assertIsArray($selected);
        $this->assertSame('jne:REG', $selected[(string) $inv1->id]);
        $this->assertSame('jnt:EZ', $selected[(string) $inv2->id]);
    }

    /**
     * Important: Single-shipment to non-default inventory must use that inventory's origin.
     */
    public function test_single_shipment_legacy_path_uses_non_default_inventory_origin(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);

        Inventory::factory()->create([
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
            'country_id' => $country->id,
        ]);
        $secondaryInventory = Inventory::factory()->create([
            'is_default' => false,
            'rajaongkir_origin_id' => '777',
            'country_id' => $country->id,
        ]);

        $product = Product::factory()->standard()->create(['weight_value' => 200, 'weight_unit' => 'g']);
        $product->mutateStock($secondaryInventory->id, 5);

        $cart = Cart::query()->create(['currency_code' => 'IDR', 'customer_id' => $user->id]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 50000,
        ]);

        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200],
                'data' => [['name' => 'JNE', 'code' => 'jne', 'service' => 'REG', 'cost' => 17000, 'etd' => '2']],
            ]),
        ]);

        $shippingAddress = [
            'first_name' => 'Budi',
            'last_name' => 'S',
            'street_address' => 'Jl. A 1',
            'postal_code' => '10110',
            'city' => 'Jakarta',
            'country_id' => $country->id,
            'rajaongkir_destination_id' => '152',
        ];

        // Pre-populate allocation plan with single non-default shipment
        $allocationPlan = new AllocationPlan([
            new ShipmentDraft(
                inventory_id: $secondaryInventory->id,
                lines: [
                    [
                        'purchasable_type' => $product->getMorphClass(),
                        'purchasable_id' => $product->id,
                        'qty' => 1,
                    ],
                ],
            ),
        ]);

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                CheckoutSession::KEY => [
                    'shipping_address' => $shippingAddress,
                ],
                CheckoutSession::ALLOCATION_PLAN => $allocationPlan,
            ])
            ->post(route('shop.checkout.shipping-option'), [
                'service_code' => 'jne:REG',
            ])
            ->assertRedirect(route('shop.checkout.index'));

        Http::assertSent(function (ClientRequest $request): bool {
            return (string) $request['origin'] === '777';
        });
    }
}
