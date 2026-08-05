<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\PaymentMethod;
use Tests\TestCase;

final class FooterCommercePropsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config()->set('komerce.enabled', true);
        config()->set('komerce.api_key', 'test-key');
        config()->set('shopper.payment.drivers.stripe.enabled', false);
    }

    public function test_home_shares_payment_and_courier_logos_for_footer(): void
    {
        PaymentMethod::factory()->create([
            'slug' => 'cod',
            'title' => 'Cash on delivery',
            'driver' => 'manual',
            'is_enabled' => true,
        ]);

        PaymentMethod::factory()->create([
            'slug' => 'komerce-qris',
            'title' => 'QRIS',
            'driver' => 'komerce',
            'is_enabled' => true,
            'metadata' => json_encode(['payment_type' => 'qris'], JSON_THROW_ON_ERROR),
        ]);

        PaymentMethod::factory()->create([
            'slug' => 'stripe',
            'title' => 'Stripe Card',
            'driver' => 'stripe',
            'is_enabled' => true,
        ]);

        foreach (['jne' => 'JNE Express', 'jnt' => 'J&T Express', 'sicepat' => 'SiCepat Ekspres'] as $slug => $name) {
            Carrier::query()->create([
                'slug' => $slug,
                'name' => $name,
                'driver' => 'rajaongkir',
                'is_enabled' => true,
            ]);
        }

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shop.payment_methods', 2)
                ->where('shop.payment_methods.0.key', 'cod')
                ->where('shop.payment_methods.0.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/payments/cod.svg'))
                ->where('shop.payment_methods.1.key', 'komerce-qris')
                ->where('shop.payment_methods.1.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/payments/qris.svg'))
                ->has('shop.shipping_couriers', 3)
                ->where('shop.shipping_couriers.0.code', 'jne')
                ->where('shop.shipping_couriers.1.code', 'jnt')
                ->where('shop.shipping_couriers.2.code', 'sicepat')
            );
    }

    public function test_shipping_couriers_empty_when_komerce_disabled(): void
    {
        config()->set('komerce.enabled', false);

        Carrier::query()->create([
            'slug' => 'jne',
            'name' => 'JNE Express',
            'driver' => 'rajaongkir',
            'is_enabled' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shop.shipping_couriers', 0)
            );
    }

    public function test_shipping_couriers_include_manual_when_komerce_disabled(): void
    {
        config()->set('komerce.enabled', false);

        Carrier::query()->create([
            'slug' => 'manual',
            'name' => 'Kurir Toko',
            'driver' => 'manual',
            'is_enabled' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shop.shipping_couriers', 1)
                ->where('shop.shipping_couriers.0.code', 'manual')
            );
    }
}
