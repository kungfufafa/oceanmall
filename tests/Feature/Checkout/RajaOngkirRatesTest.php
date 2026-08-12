<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\CheckoutSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Shopper\Cart\CartManager;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Product;
use Shopper\Core\Models\Zone;
use Tests\TestCase;

final class RajaOngkirRatesTest extends TestCase
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
        config()->set('komerce.shipping_cost_api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
    }

    private function seedCountryZoneInventory(): array
    {
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        foreach (['jne' => 'JNE Express', 'jnt' => 'J&T Express'] as $slug => $name) {
            $carrier = Carrier::query()->create([
                'slug' => $slug,
                'name' => $name,
                'driver' => 'rajaongkir',
                'is_enabled' => true,
            ]);
            $zone->carriers()->attach($carrier->id);
        }

        Inventory::factory()->create([
            'country_id' => $country->id,
            'is_default' => true,
            'rajaongkir_origin_id' => '501',
        ]);

        return [$country, $zone];
    }

    private function fakeCartManager(): void
    {
        $this->app->instance(CartManager::class, new class
        {
            public function addAddress(Cart $cart): void {}

            public function calculate(Cart $cart): array
            {
                return [
                    'total' => 100000,
                    'taxTotal' => 0,
                    'discountTotal' => 0,
                ];
            }
        });
    }

    private function fakeDomesticCostResponse(): void
    {
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
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 18000,
                        'etd' => '2-3',
                    ],
                    [
                        'name' => 'J&T Express',
                        'code' => 'jnt',
                        'service' => 'EZ',
                        'description' => 'Regular Service',
                        'cost' => 16000,
                        'etd' => '3',
                    ],
                ],
            ]),
        ]);
    }

    public function test_checkout_delivery_options_include_rajaongkir_rates_from_default_inventory(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        [$country] = $this->seedCountryZoneInventory();

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'weight_value' => 75,
            'weight_unit' => 'g',
        ]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 2,
            'unit_price_amount' => 50000,
        ]);

        $this->fakeCartManager();
        $this->fakeDomesticCostResponse();

        $this->actingAs($user)
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
                        'rajaongkir_destination_id' => '114',
                    ],
                ],
            ])
            ->get(route('shop.checkout.index', ['step' => 2]), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.deliveryOptions.0.service_code', 'jne:REG')
            ->assertJsonPath('props.deliveryOptions.0.service_name', 'REG')
            ->assertJsonPath('props.deliveryOptions.0.amount', 18000)
            ->assertJsonPath('props.deliveryOptions.0.currency', 'IDR')
            ->assertJsonPath('props.deliveryOptions.0.carrier_code', 'jne')
            ->assertJsonPath('props.deliveryOptions.0.carrier_name', 'JNE Express')
            ->assertJsonPath('props.deliveryOptions.0.estimated_days', '2-3')
            ->assertJsonPath('props.deliveryOptions.1.service_code', 'jnt:EZ');

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://shipping.example.test/api/v1/calculate/domestic-cost'
                && $request->hasHeader('key', 'test-komerce-key')
                && (string) $request['origin'] === '501'
                && (string) $request['destination'] === '114'
                && (int) $request['weight'] === 150
                && $request['courier'] === 'jne:jnt';
        });
    }

    public function test_saving_shipping_address_persists_rajaongkir_destination_id_for_delivery_rates(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        [$country] = $this->seedCountryZoneInventory();

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'weight_value' => 75,
            'weight_unit' => 'g',
        ]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 2,
            'unit_price_amount' => 50000,
        ]);

        $this->fakeCartManager();
        $this->fakeDomesticCostResponse();

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'zone_country_code' => $country->cca2,
            ])
            ->post(route('shop.checkout.shipping-address'), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Merdeka 1',
                'postal_code' => '10110',
                'city' => 'Jakarta',
                'state' => 'DKI Jakarta',
                'phone_number' => '081234567890',
                'rajaongkir_destination_id' => '114',
            ])
            ->assertRedirect(route('shop.checkout.index'))
            ->assertSessionHas(CheckoutSession::SHIPPING_ADDRESS.'.rajaongkir_destination_id', '114');

        $this->get(route('shop.checkout.index', ['step' => 2]), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.deliveryOptions.0.service_code', 'jne:REG');

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://shipping.example.test/api/v1/calculate/domestic-cost'
                && (string) $request['destination'] === '114'
                && (int) $request['weight'] === 150;
        });
    }

    public function test_rajaongkir_rates_send_metric_kg_packages_as_grams_for_heavy_products(): void
    {
        $this->fakeRajaOngkirConfig();

        $user = User::factory()->create();
        [$country] = $this->seedCountryZoneInventory();

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        $product = Product::factory()->standard()->create([
            'name' => 'Industrial Grinder',
            'weight_value' => 150,
            'weight_unit' => 'kg',
        ]);
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 100000,
        ]);

        $this->fakeCartManager();
        $this->fakeDomesticCostResponse();

        $this->actingAs($user)
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
                        'rajaongkir_destination_id' => '114',
                    ],
                ],
            ])
            ->get(route('shop.checkout.index', ['step' => 2]), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.deliveryOptions.0.service_code', 'jne:REG');

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://shipping.example.test/api/v1/calculate/domestic-cost'
                && (int) $request['weight'] === 150000;
        });
    }
}
