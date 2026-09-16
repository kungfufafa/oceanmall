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

    public function test_address_book_api_edits_deletes_and_sets_default_while_keeping_pin(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/addresses', [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai 1',
            'postal_code' => '12220',
            'city' => 'Jakarta Selatan',
            'state' => 'DKI Jakarta',
            'phone_number' => '081234567890',
            'country_id' => $country->id,
            'type' => 'shipping',
            'rajaongkir_destination_id' => '17547',
            'rajaongkir_destination_label' => 'KEBAYORAN BARU, JAKARTA SELATAN',
            'rajaongkir_pin_point' => '-6.238000,106.783000',
        ])->assertCreated();

        $other = $this->postJson('/api/v1/addresses', [
            'first_name' => 'Siti',
            'last_name' => 'Aminah',
            'street_address' => 'Jl. Asia Afrika 2',
            'postal_code' => '40111',
            'city' => 'Bandung',
            'state' => 'Jawa Barat',
            'phone_number' => '081298765432',
            'country_id' => $country->id,
            'type' => 'shipping',
            'shipping_default' => true,
            'rajaongkir_destination_id' => '9801',
            'rajaongkir_destination_label' => 'BANDUNG WETAN, BANDUNG',
            'rajaongkir_pin_point' => '-6.910000,107.610000',
        ])->assertCreated();

        $firstId = $user->addresses()->where('street_address', 'Jl. Melawai 1')->value('id');
        $secondId = $other->json('data.id');
        $this->assertIsInt($firstId);
        $this->assertIsInt($secondId);

        $this->patchJson("/api/v1/addresses/{$firstId}", [
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai 9',
            'postal_code' => '12220',
            'city' => 'Jakarta Selatan',
            'state' => 'DKI Jakarta',
            'phone_number' => '081234567890',
            'country_id' => $country->id,
            'type' => 'shipping',
            'rajaongkir_destination_id' => '17547',
            'rajaongkir_destination_label' => 'KEBAYORAN BARU, JAKARTA SELATAN',
            'rajaongkir_pin_point' => '-6.239111,106.784222',
        ])
            ->assertOk()
            ->assertJsonPath('data.street_address', 'Jl. Melawai 9')
            ->assertJsonPath('data.rajaongkir_destination_id', '17547')
            ->assertJsonPath('data.rajaongkir_pin_point', '-6.239111,106.784222');

        $this->patchJson("/api/v1/addresses/{$firstId}/default-shipping")
            ->assertOk()
            ->assertJsonPath('data.shipping_default', true);

        $this->getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonPath('data.0.id', $firstId)
            ->assertJsonPath('data.0.shipping_default', true)
            ->assertJsonPath('data.0.rajaongkir_pin_point', '-6.239111,106.784222');

        $this->deleteJson("/api/v1/addresses/{$secondId}")
            ->assertOk()
            ->assertJsonPath('message', 'Alamat dihapus.');

        $this->getJson('/api/v1/addresses')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $firstId)
            ->assertJsonPath('data.0.rajaongkir_destination_id', '17547');
    }

    public function test_expo_address_book_edits_deletes_and_sets_default_shipping(): void
    {
        $page = file_get_contents(base_path('mobile/app/(tabs)/account.tsx'));

        $this->assertIsString($page);
        $this->assertStringContainsString("method: 'PATCH'", $page);
        $this->assertStringContainsString("method: 'DELETE'", $page);
        $this->assertStringContainsString('default-shipping', $page);
        $this->assertStringContainsString('`/addresses/${', $page);
        $this->assertStringContainsString('rajaongkir_destination_id', $page);
        $this->assertStringContainsString('rajaongkir_pin_point', $page);
        $this->assertStringContainsString('startEdit', $page);
    }
}
