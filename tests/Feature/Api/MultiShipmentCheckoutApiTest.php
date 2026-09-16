<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Zone;
use Tests\TestCase;

final class MultiShipmentCheckoutApiTest extends TestCase
{
    use RefreshDatabase;

    private function fakeKomerceShippingConfig(): void
    {
        config()->set('komerce.shipping_cost_api_key', 'test-cost-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
    }

    private function fakeKomercePaymentConfig(): void
    {
        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.payment_base_url', 'https://payment.example.test/user');
        config()->set('komerce.webhook_secret', 'webhook-secret');
    }

    /**
     * Domestic-cost fake returning both a JNE and a J&T option for every quote.
     */
    private function fakeDomesticCost(int $jneAmount = 18000, int $jntAmount = 16000): void
    {
        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
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
            ]),
        ]);
    }

    private function createZonedCountry(): Country
    {
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        return $country;
    }

    /**
     * Two warehouses each holding 1 unit — a qty=2 cart is forced to split.
     *
     * @return array{User, Product, Inventory, Inventory}
     */
    private function splitCartFixture(Country $country): array
    {
        $user = User::factory()->create();

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
            'name' => 'Split API Product',
            'published_at' => now()->subDay(),
            'weight_value' => 100,
            'weight_unit' => 'g',
        ]);
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

        return [$user, $product, $defaultInventory, $secondaryInventory];
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingAddressPayload(): array
    {
        return [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai 1',
            'postal_code' => '12220',
            'city' => 'Jakarta Selatan',
            'state' => 'DKI Jakarta',
            'phone_number' => '081234567890',
            'rajaongkir_destination_id' => '17547',
            'rajaongkir_pin_point' => '-6.2380,106.7830',
        ];
    }

    public function test_checkout_show_returns_per_shipment_allocation_and_rates_for_multi_warehouse_cart(): void
    {
        $this->fakeKomerceShippingConfig();
        $this->fakeDomesticCost();

        $country = $this->createZonedCountry();
        [$user, $product, $defaultInventory, $secondaryInventory] = $this->splitCartFixture($country);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/checkout/shipping-address', $this->shippingAddressPayload())
            ->assertOk();

        $response = $this->getJson('/api/v1/checkout')->assertOk();

        $allocation = $response->json('data.allocation');
        $this->assertIsArray($allocation);
        $this->assertCount(2, $allocation);

        $inventoryIds = array_column($allocation, 'inventory_id');
        $this->assertContains($defaultInventory->id, $inventoryIds);
        $this->assertContains($secondaryInventory->id, $inventoryIds);

        $inventoryNames = array_column($allocation, 'inventory_name');
        $this->assertContains('Gudang Jakarta', $inventoryNames);
        $this->assertContains('Gudang Cirebon', $inventoryNames);

        foreach ($allocation as $package) {
            $this->assertSame('Split API Product', $package['lines'][0]['name']);
            $this->assertSame($product->id, $package['lines'][0]['purchasable_id']);
            $this->assertSame(1, $package['lines'][0]['qty']);

            $serviceCodes = array_column($package['rates'], 'service_code');
            $this->assertContains('jne:REG', $serviceCodes);
            $this->assertContains('jnt:EZ', $serviceCodes);
        }

        // Multi-warehouse carts have no flat single-shipment rate list.
        $this->assertSame([], $response->json('data.shipping_rates'));
    }

    public function test_single_service_code_is_rejected_for_multi_warehouse_cart(): void
    {
        $this->fakeKomerceShippingConfig();
        $this->fakeDomesticCost();

        $country = $this->createZonedCountry();
        [$user] = $this->splitCartFixture($country);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/checkout/shipping-address', $this->shippingAddressPayload())
            ->assertOk();

        $this->postJson('/api/v1/checkout/shipping-option', [
            'service_code' => 'jne:REG',
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', fn (mixed $message): bool => is_string($message) && str_contains($message, 'rates'));
    }

    public function test_per_shipment_selection_then_place_order_creates_multiple_order_shipments(): void
    {
        $this->fakeKomerceShippingConfig();
        $this->fakeKomercePaymentConfig();

        $country = $this->createZonedCountry();
        $zone = Zone::query()->firstOrFail();

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
                    'payment_id' => 'KPAY-SPLIT-1',
                    'va_number' => '1234567890',
                    'bank_code' => 'BCA',
                    'amount' => 234000,
                    'status' => 'PENDING',
                ],
            ]),
        ]);

        [$user, $product, $defaultInventory, $secondaryInventory] = $this->splitCartFixture($country);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/checkout/shipping-address', $this->shippingAddressPayload())
            ->assertOk();

        $this->postJson('/api/v1/checkout/shipping-option', [
            'rates' => [
                (string) $defaultInventory->id => 'jne:REG',
                (string) $secondaryInventory->id => 'jnt:EZ',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('data.shipping_option.service_code', 'split-shipment')
            ->assertJsonPath('data.shipping_option.price', 34000);

        $orderResponse = $this->postJson('/api/v1/checkout/place-order', [
            'payment_method_id' => $paymentMethod->id,
        ])->assertCreated();

        $orderId = $orderResponse->json('data.order_id');

        $shipments = OrderShipment::query()
            ->with('lines')
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();

        $this->assertCount(2, $shipments);

        $byInventory = $shipments->keyBy('inventory_id');

        $jakarta = $byInventory->get($defaultInventory->id);
        $this->assertNotNull($jakarta);
        $this->assertSame('jne', $jakarta->carrier_code);
        $this->assertSame(18000, $jakarta->cost);
        $this->assertSame(1, $jakarta->lines->first()->qty);
        $this->assertSame($product->id, $jakarta->lines->first()->purchasable_id);

        $cirebon = $byInventory->get($secondaryInventory->id);
        $this->assertNotNull($cirebon);
        $this->assertSame('jnt', $cirebon->carrier_code);
        $this->assertSame(16000, $cirebon->cost);
        $this->assertSame(1, $cirebon->lines->first()->qty);

        // 2 × 100000 items + 18000 + 16000 shipping
        $this->assertSame(234000, (int) $orderResponse->json('data.amount'));
    }

    public function test_shipping_address_persists_pin_point_from_explicit_field_and_coordinates(): void
    {
        $this->fakeKomerceShippingConfig();

        $country = $this->createZonedCountry();
        $user = User::factory()->create();

        Sanctum::actingAs($user);

        // Explicit "lat,long" pin point field.
        $this->postJson('/api/v1/checkout/shipping-address', [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai 1',
            'postal_code' => '12220',
            'city' => 'Jakarta Selatan',
            'phone_number' => '081234567890',
            'rajaongkir_destination_id' => '17547',
            'rajaongkir_pin_point' => '-6.2380,106.7830',
        ])
            ->assertOk()
            ->assertJsonPath('data.shipping_address.rajaongkir_pin_point', '-6.2380,106.7830');

        $address = $user->addresses()
            ->where('type', AddressType::Shipping)
            ->firstOrFail();
        $metadata = json_decode((string) $address->getRawOriginal('metadata'), true);
        $this->assertSame('-6.2380,106.7830', $metadata['rajaongkir_pin_point']);
        $this->assertSame($country->id, $address->country_id);

        // Separate latitude/longitude fields are combined into a pin point.
        $this->postJson('/api/v1/checkout/shipping-address', [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Sudirman 2',
            'postal_code' => '10220',
            'city' => 'Jakarta Pusat',
            'phone_number' => '081234567890',
            'rajaongkir_destination_id' => '17547',
            'latitude' => -6.2088,
            'longitude' => 106.8456,
        ])
            ->assertOk()
            ->assertJsonPath('data.shipping_address.rajaongkir_pin_point', '-6.2088,106.8456');

        $second = $user->addresses()
            ->where('street_address', 'Jl. Sudirman 2')
            ->firstOrFail();
        $secondMetadata = json_decode((string) $second->getRawOriginal('metadata'), true);
        $this->assertSame('-6.2088,106.8456', $secondMetadata['rajaongkir_pin_point']);
    }
}
