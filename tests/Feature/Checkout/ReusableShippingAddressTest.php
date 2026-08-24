<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\CheckoutSession;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Zone;
use Tests\TestCase;

final class ReusableShippingAddressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.enabled', true);
        config()->set('komerce.shipping_cost_api_key', 'test-key');

        $manifest = public_path('build/manifest.json');
        $this->withHeader(
            'X-Inertia-Version',
            file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        );
    }

    /**
     * @return array{0: User, 1: Country, 2: Cart}
     */
    private function customerWithCart(): array
    {
        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $zone = Zone::factory()->create(['is_enabled' => true]);
        $zone->countries()->attach($country->id);

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
            'unit_price_amount' => 50000,
        ]);

        return [$user, $country, $cart];
    }

    public function test_saving_checkout_address_persists_reusable_user_address_with_district(): void
    {
        [$user, $country, $cart] = $this->customerWithCart();

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'zone_country_code' => 'ID',
            ])
            ->post(route('shop.checkout.shipping-address'), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Melawai Raya No. 1',
                'postal_code' => '12240',
                'city' => 'Jakarta Selatan',
                'state' => 'DKI Jakarta',
                'phone_number' => '081234567890',
                'rajaongkir_destination_id' => '17549',
                'rajaongkir_destination_label' => 'KEBAYORAN LAMA SELATAN, JAKARTA SELATAN',
            ])
            ->assertRedirect(route('shop.checkout.index'));

        $address = Address::query()->where('user_id', $user->id)->first();
        $this->assertNotNull($address);
        $this->assertSame('Jl. Melawai Raya No. 1', $address->street_address);
        $this->assertTrue($address->shipping_default);
        $this->assertSame(AddressType::Shipping, $address->type);

        $metadata = is_string($address->metadata)
            ? json_decode($address->metadata, true)
            : $address->metadata;
        $this->assertIsArray($metadata);
        $this->assertSame('17549', data_get($metadata, 'rajaongkir_destination_id'));
        $this->assertSame(
            'KEBAYORAN LAMA SELATAN, JAKARTA SELATAN',
            data_get($metadata, 'rajaongkir_destination_label'),
        );
    }

    public function test_saving_same_checkout_address_updates_existing_instead_of_duplicating(): void
    {
        [$user, $country, $cart] = $this->customerWithCart();

        Address::query()->create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai Raya No. 1',
            'postal_code' => '12240',
            'city' => 'Jakarta Selatan',
            'phone_number' => '081111111111',
            'type' => AddressType::Shipping,
            'shipping_default' => true,
            'billing_default' => false,
            'metadata' => json_encode([
                'rajaongkir_destination_id' => '111',
                'rajaongkir_destination_label' => 'OLD',
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'zone_country_code' => 'ID',
            ])
            ->post(route('shop.checkout.shipping-address'), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Melawai Raya No. 1',
                'postal_code' => '12240',
                'city' => 'Jakarta Selatan',
                'phone_number' => '081234567890',
                'rajaongkir_destination_id' => '17549',
                'rajaongkir_destination_label' => 'KEBAYORAN LAMA SELATAN',
            ])
            ->assertRedirect();

        $this->assertSame(1, Address::query()->where('user_id', $user->id)->count());
        $address = Address::query()->where('user_id', $user->id)->firstOrFail();
        $metadata = is_string($address->metadata)
            ? json_decode($address->metadata, true)
            : $address->metadata;
        $this->assertSame('17549', data_get($metadata, 'rajaongkir_destination_id'));
        $this->assertSame('081234567890', $address->phone_number);
    }

    public function test_checkout_auto_applies_default_saved_address_with_district(): void
    {
        [$user, $country, $cart] = $this->customerWithCart();

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
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'zone_country_code' => 'ID',
            ])
            ->get(route('shop.checkout.index'), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.shippingAddress.street_address', 'Jl. Melawai Raya No. 1')
            ->assertJsonPath('props.shippingAddress.rajaongkir_destination_id', '17549')
            ->assertJsonPath('props.step', 2);

        $this->assertSame(
            '17549',
            session(CheckoutSession::SHIPPING_ADDRESS.'.rajaongkir_destination_id'),
        );
    }

    public function test_saved_addresses_payload_includes_rajaongkir_fields(): void
    {
        [$user, $country, $cart] = $this->customerWithCart();

        Address::query()->create([
            'user_id' => $user->id,
            'country_id' => $country->id,
            'first_name' => 'Siti',
            'last_name' => 'Aminah',
            'street_address' => 'Jl. Asia Afrika 2',
            'postal_code' => '40111',
            'city' => 'Bandung',
            'type' => AddressType::Shipping,
            'shipping_default' => false,
            'billing_default' => false,
            'metadata' => json_encode([
                'rajaongkir_destination_id' => '99001',
                'rajaongkir_destination_label' => 'BANDUNG WETAN',
            ], JSON_THROW_ON_ERROR),
        ]);

        // Put a session address so checkout does not auto-apply and stay on step 1 listing.
        $this->actingAs($user)
            ->withSession([
                config('shopper.cart.session.key', 'shopper_cart') => $cart->id,
                'zone_country_code' => 'ID',
                CheckoutSession::KEY => [
                    'shipping_address' => [
                        'first_name' => 'Temp',
                        'last_name' => 'User',
                        'street_address' => 'Temp',
                        'postal_code' => '11111',
                        'city' => 'Jakarta',
                        'country_id' => $country->id,
                        'rajaongkir_destination_id' => '1',
                        'rajaongkir_destination_label' => 'TEMP',
                    ],
                ],
            ])
            ->get(route('shop.checkout.index', ['step' => 1]), ['X-Inertia' => 'true'])
            ->assertOk()
            ->assertJsonPath('props.savedAddresses.0.rajaongkir_destination_id', '99001')
            ->assertJsonPath('props.savedAddresses.0.rajaongkir_destination_label', 'BANDUNG WETAN');
    }
}
