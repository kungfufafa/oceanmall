<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use App\Support\CustomerCheckoutState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Zone;
use Tests\TestCase;

final class CheckoutDefaultAddressApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.enabled', true);
        config()->set('komerce.shipping_cost_api_key', 'test-key');
    }

    public function test_checkout_auto_applies_default_saved_address_with_district_and_pin(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        Address::query()->create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai Raya No. 1',
            'postal_code' => '12240',
            'city' => 'Jakarta Selatan',
            'state' => 'DKI Jakarta',
            'phone_number' => '081234567890',
            'type' => AddressType::Shipping,
            'shipping_default' => true,
            'billing_default' => false,
            'metadata' => json_encode([
                'rajaongkir_destination_id' => '17549',
                'rajaongkir_destination_label' => 'KEBAYORAN LAMA SELATAN',
                'rajaongkir_pin_point' => '-6.2380,106.7830',
            ], JSON_THROW_ON_ERROR),
        ]);

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        $product = Product::factory()->standard()->create();
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 50_000,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('data.shipping_address.street_address', 'Jl. Melawai Raya No. 1')
            ->assertJsonPath('data.shipping_address.rajaongkir_destination_id', '17549')
            ->assertJsonPath('data.shipping_address.rajaongkir_destination_label', 'KEBAYORAN LAMA SELATAN')
            ->assertJsonPath('data.shipping_address.rajaongkir_pin_point', '-6.2380,106.7830')
            ->assertJsonPath('data.shipping_address.saved_address_id', Address::query()->where('user_id', $user->id)->value('id'));

        $state = resolve(CustomerCheckoutState::class)->get($user);
        $this->assertSame('17549', data_get($state, 'shipping_address.rajaongkir_destination_id'));
        $this->assertSame('-6.2380,106.7830', data_get($state, 'shipping_address.rajaongkir_pin_point'));
    }

    public function test_checkout_does_not_auto_apply_saved_address_missing_district(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

        Address::query()->create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'first_name' => 'Siti',
            'last_name' => 'Aminah',
            'street_address' => 'Jl. Asia Afrika 2',
            'postal_code' => '40111',
            'city' => 'Bandung',
            'type' => AddressType::Shipping,
            'shipping_default' => true,
            'billing_default' => false,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/checkout')
            ->assertOk()
            ->assertJsonPath('data.shipping_address', null)
            ->assertJsonPath('data.saved_addresses.0.street_address', 'Jl. Asia Afrika 2');
    }

    public function test_expo_address_book_writes_rajaongkir_district_and_pin(): void
    {
        $page = file_get_contents(base_path('mobile/app/(tabs)/account.tsx'));

        $this->assertIsString($page);
        $this->assertStringContainsString("method: 'POST'", $page);
        $this->assertStringContainsString("'/addresses'", $page);
        $this->assertStringContainsString('rajaongkir_destination_id', $page);
        $this->assertStringContainsString('rajaongkir_pin_point', $page);
        $this->assertStringContainsString('/checkout/destinations', $page);
        $this->assertStringContainsString('Gunakan lokasi', $page);
    }
}
