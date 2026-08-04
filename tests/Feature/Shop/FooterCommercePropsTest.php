<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
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
        config()->set('komerce.couriers', ['jne', 'jnt', 'sicepat']);
        config()->set('shopper.payment.drivers.stripe.enabled', false);
    }

    public function test_home_shares_payment_and_courier_logos_for_footer(): void
    {
        PaymentMethod::factory()->create([
            'title' => 'Cash on delivery',
            'driver' => 'manual',
            'is_enabled' => true,
        ]);

        PaymentMethod::factory()->create([
            'title' => 'Virtual Account / QRIS (Komerce)',
            'driver' => 'komerce',
            'is_enabled' => true,
        ]);

        PaymentMethod::factory()->create([
            'title' => 'Stripe Card',
            'driver' => 'stripe',
            'is_enabled' => true,
        ]);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shop.payment_methods')
                ->where('shop.payment_methods.0.key', 'cod')
                ->where('shop.payment_methods.0.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/commerce/cod.svg'))
                ->where('shop.payment_methods.1.key', 'qris')
                ->where('shop.payment_methods.1.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/commerce/qris.png'))
                ->has('shop.shipping_couriers', 3)
                ->where('shop.shipping_couriers.0.code', 'jne')
                ->where('shop.shipping_couriers.0.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/commerce/jne.svg'))
                ->where('shop.shipping_couriers.1.code', 'jnt')
                ->where('shop.shipping_couriers.1.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/commerce/jnt.svg'))
                ->where('shop.shipping_couriers.2.code', 'sicepat')
                ->where('shop.shipping_couriers.2.logo', fn ($logo) => is_string($logo) && str_contains($logo, 'images/commerce/sicepat.svg'))
            );
    }

    public function test_shipping_couriers_empty_when_komerce_disabled(): void
    {
        config()->set('komerce.enabled', false);

        $this->get(route('home'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shop.shipping_couriers', 0)
            );
    }
}
