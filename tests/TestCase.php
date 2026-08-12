<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\Features;
use Shopper\Core\Models\Setting;
use Shopper\Database\Seeders\PermissionRoleTableSeeder;
use Shopper\Database\Seeders\PermissionsTableSeeder;
use Spatie\Permission\Models\Role;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep Feature tests hermetic: local UAT credentials in `.env` must not
        // enable a product-specific integration and change test behavior.
        config([
            'komerce.enabled' => null,
            'komerce.payment_api_key' => '',
            'komerce.shipping_cost_api_key' => '',
            'komerce.shipping_delivery_api_key' => '',
            'komerce.qrisly_api_key' => '',
            'komerce.qrisly_qris_id' => '',
            'komerce.webhook_secret' => '',
        ]);
    }

    /**
     * Seed Shopper settings + permissions so /cpanel Dashboard middleware passes
     * for admins and fails cleanly (403) for non-admins.
     */
    protected function configureShopperCpanel(): void
    {
        Setting::query()->updateOrCreate(['key' => 'email'], ['value' => 'ops@oceanmall.test']);
        Setting::query()->updateOrCreate(['key' => 'street_address'], ['value' => 'Jl. Test 1']);
        Cache::forget('shopper-setting.email');
        Cache::forget('shopper-setting.street_address');

        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);

        $this->seed(PermissionsTableSeeder::class);
        $this->seed(PermissionRoleTableSeeder::class);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
