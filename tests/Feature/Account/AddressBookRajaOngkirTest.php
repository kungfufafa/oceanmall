<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\Address;
use Shopper\Core\Models\Country;
use Tests\TestCase;

final class AddressBookRajaOngkirTest extends TestCase
{
    use RefreshDatabase;

    public function test_address_book_vue_collects_destination_and_pin_point(): void
    {
        $page = file_get_contents(resource_path('js/pages/account/addresses.vue'));

        $this->assertIsString($page);
        $this->assertStringContainsString('rajaongkir_destination_id', $page);
        $this->assertStringContainsString('rajaongkir_pin_point', $page);
        $this->assertStringContainsString('/checkout/destinations', $page);
        $this->assertStringContainsString('Gunakan lokasi saya', $page);
    }

    public function test_customer_can_save_destination_and_pin_on_account_address(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);

        $this->actingAs($user)
            ->post(route('account.addresses.store'), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Melawai 1',
                'postal_code' => '12220',
                'city' => 'Jakarta Selatan',
                'state' => 'DKI Jakarta',
                'phone_number' => '081234567890',
                'country_id' => $country->id,
                'type' => AddressType::Shipping->value,
                'rajaongkir_destination_id' => '17547',
                'rajaongkir_destination_label' => 'KEBAYORAN BARU, JAKARTA SELATAN',
                'rajaongkir_pin_point' => '-6.238000,106.783000',
            ])
            ->assertRedirect(route('account.addresses'));

        $address = $user->addresses()->first();
        $this->assertInstanceOf(Address::class, $address);
        $metadata = is_array($address->metadata) ? $address->metadata : json_decode((string) $address->metadata, true);

        $this->assertSame('17547', data_get($metadata, 'rajaongkir_destination_id'));
        $this->assertSame('KEBAYORAN BARU, JAKARTA SELATAN', data_get($metadata, 'rajaongkir_destination_label'));
        $this->assertSame('-6.238000,106.783000', data_get($metadata, 'rajaongkir_pin_point'));
    }

    public function test_address_index_exposes_rajaongkir_fields_like_checkout(): void
    {
        $this->withoutVite();

        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $user->addresses()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai 1',
            'postal_code' => '12220',
            'city' => 'Jakarta Selatan',
            'country_id' => $country->id,
            'type' => AddressType::Shipping,
            'metadata' => json_encode([
                'rajaongkir_destination_id' => '17547',
                'rajaongkir_destination_label' => 'KEBAYORAN BARU, JAKARTA SELATAN',
                'rajaongkir_pin_point' => '-6.238000,106.783000',
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user)
            ->get(route('account.addresses'))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('account/addresses')
                    ->where('addresses.0.rajaongkir_destination_id', '17547')
                    ->where('addresses.0.rajaongkir_destination_label', 'KEBAYORAN BARU, JAKARTA SELATAN')
                    ->where('addresses.0.rajaongkir_pin_point', '-6.238000,106.783000'),
            );
    }

    public function test_updating_street_keeps_existing_pin_when_pin_omitted(): void
    {
        $user = User::factory()->create();
        $country = Country::factory()->create(['cca2' => 'ID']);
        $address = $user->addresses()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Melawai 1',
            'postal_code' => '12220',
            'city' => 'Jakarta Selatan',
            'country_id' => $country->id,
            'type' => AddressType::Shipping,
            'metadata' => json_encode([
                'rajaongkir_destination_id' => '17547',
                'rajaongkir_pin_point' => '-6.238000,106.783000',
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->actingAs($user)
            ->patch(route('account.addresses.update', $address), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Melawai 2',
                'postal_code' => '12220',
                'city' => 'Jakarta Selatan',
                'country_id' => $country->id,
                'type' => AddressType::Shipping->value,
            ])
            ->assertRedirect(route('account.addresses'));

        $address->refresh();
        $metadata = is_array($address->metadata) ? $address->metadata : json_decode((string) $address->metadata, true);
        $this->assertSame('Jl. Melawai 2', $address->street_address);
        $this->assertSame('17547', data_get($metadata, 'rajaongkir_destination_id'));
        $this->assertSame('-6.238000,106.783000', data_get($metadata, 'rajaongkir_pin_point'));
    }
}
