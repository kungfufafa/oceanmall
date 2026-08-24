<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
use Shopper\Shipping\Facades\Shipping;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ShopperDriverAdminTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->configureShopperCpanel();

        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        return $admin;
    }

    public function test_shopper_payment_and_shipping_pages_resolve_komerce_drivers(): void
    {
        config()->set('komerce.payment_api_key', 'test-payment-key');
        config()->set('komerce.shipping_cost_api_key', 'test-cost-key');
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');

        $admin = $this->admin();

        PaymentMethod::query()->create([
            'title' => 'QRIS Komerce',
            'slug' => 'komerce-qris',
            'driver' => 'komerce',
            'is_enabled' => true,
        ]);
        Carrier::query()->create([
            'name' => 'JNE Express',
            'slug' => 'jne',
            'driver' => 'rajaongkir',
            'is_enabled' => true,
        ]);

        $this->assertTrue(Payment::isConfigured('komerce'));
        $this->assertTrue(Shipping::isConfigured('rajaongkir'));
        $this->assertTrue(Shipping::isConfigured('komerce'));

        $this->actingAs($admin)
            ->get(route('shopper.settings.payment-methods'))
            ->assertOk()
            ->assertSee('QRIS Komerce', false)
            ->assertSee('Komerce', false);

        $this->actingAs($admin)
            ->get(route('shopper.settings.carriers'))
            ->assertOk()
            ->assertSee('JNE Express', false)
            ->assertSee('RajaOngkir', false);
    }
}
