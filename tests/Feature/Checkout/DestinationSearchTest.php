<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

final class DestinationSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_search_destinations_when_komerce_enabled(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.enabled', true);
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');

        Http::fake([
            'https://shipping.example.test/api/v1/destination/domestic-destination*' => Http::response([
                'meta' => ['code' => 200],
                'data' => [
                    [
                        'id' => 152,
                        'label' => 'Jakarta Selatan, Kebayoran Baru',
                        'province_name' => 'DKI Jakarta',
                        'city_name' => 'Jakarta Selatan',
                        'district_name' => 'Kebayoran Baru',
                        'subdistrict_name' => 'Senayan',
                        'zip_code' => '12190',
                    ],
                ],
            ]),
        ]);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('shop.checkout.destinations', ['q' => 'jakarta']))
            ->assertOk()
            ->assertJsonPath('data.0.id', '152')
            ->assertJsonPath('data.0.label', 'Jakarta Selatan, Kebayoran Baru');
    }

    public function test_destination_search_returns_empty_when_komerce_disabled(): void
    {
        config()->set('komerce.enabled', false);
        Http::fake();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson(route('shop.checkout.destinations', ['q' => 'jakarta']))
            ->assertOk()
            ->assertJsonPath('data', []);

        Http::assertNothingSent();
    }

    public function test_shipping_address_requires_destination_when_komerce_enabled(): void
    {
        config()->set('komerce.api_key', 'test-key');
        config()->set('komerce.enabled', true);

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('shop.checkout.shipping-address'), [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Merdeka 1',
                'postal_code' => '10110',
                'city' => 'Jakarta',
            ])
            ->assertSessionHasErrors('rajaongkir_destination_id');
    }
}
