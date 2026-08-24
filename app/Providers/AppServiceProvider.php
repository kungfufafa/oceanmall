<?php

declare(strict_types=1);

namespace App\Providers;

use App\Addons\KomerceRajaOngkir\KomerceRajaOngkirAddon;
use App\Livewire\Shopper\Pages\OrderShipments;
use App\Models\OrderShipment;
use App\Models\User;
use App\Observers\InventoryObserver;
use App\Observers\OrderShipmentObserver;
use App\Payment\KomerceDriver;
use App\Shipping\Drivers\KomerceShippingDriver;
use App\Shipping\Drivers\RajaOngkirDriver;
use App\Stock\AllocationPlanStockAllocator;
use App\Support\CheckoutAllocationContext;
use App\Support\KomerceCourierAssets;
use App\Support\KomerceFulfillmentContext;
use App\Support\KomercePaymentLookupContext;
use App\Support\KomerceTrackingContext;
use App\Support\RajaOngkirQuoteContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Shopper\Core\Contracts\StockAllocator;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
use Shopper\Shipping\Facades\Shipping;
use Shopper\ShopperPanel;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CheckoutAllocationContext::class);
        $this->app->scoped(RajaOngkirQuoteContext::class);
        $this->app->scoped(KomerceTrackingContext::class);
        $this->app->scoped(KomerceFulfillmentContext::class);
        $this->app->scoped(KomercePaymentLookupContext::class);
        $this->app->bind(StockAllocator::class, AllocationPlanStockAllocator::class);

        $this->registerShopperAddon();

        $this->app->booting(function (): void {
            config()->set(
                'shopper.components.order.pages.order-shipments',
                OrderShipments::class,
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        OrderShipment::observe(OrderShipmentObserver::class);
        Inventory::observe(InventoryObserver::class);

        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureShopperPayment();
        $this->configureShopperShipping();
        $this->configureShopperLogos();
    }

    /**
     * Register the panel addon on every scoped Shopper instance (Octane-safe).
     * Shopper boots addons immediately after resolving the panel, so this must
     * run via afterResolving rather than AppServiceProvider::boot().
     */
    protected function registerShopperAddon(): void
    {
        $this->app->afterResolving('shopper', function (ShopperPanel $panel): void {
            if ($panel->hasAddon('komerce-rajaongkir')) {
                return;
            }

            $panel->addon(new KomerceRajaOngkirAddon);
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(
            fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }

    protected function configureAuthorization(): void
    {
        Gate::define(
            'override-allocation',
            static fn (User $user, ?Order $order = null): bool => $user->isAdmin() || $user->isManager() || $user->hasPermissionTo('browse_orders'),
        );

        Gate::define(
            'print-shipment-label',
            static fn (User $user, ?Order $order = null): bool => $user->isAdmin() || $user->isManager() || $user->hasPermissionTo('browse_orders'),
        );
    }

    protected function configureShopperPayment(): void
    {
        Payment::extend('komerce', static fn (): KomerceDriver => resolve(KomerceDriver::class));

        config()->set('shopper.payment.drivers.komerce.enabled', komerce_payment_enabled() || qrisly_enabled());
    }

    protected function configureShopperShipping(): void
    {
        Shipping::extend('rajaongkir', static fn (): RajaOngkirDriver => resolve(RajaOngkirDriver::class));
        Shipping::extend('komerce', static fn (): KomerceShippingDriver => resolve(KomerceShippingDriver::class));

        config()->set('shopper.shipping.drivers.rajaongkir.enabled', komerce_shipping_cost_enabled());
        config()->set('shopper.shipping.drivers.komerce.enabled', komerce_shipping_delivery_enabled());
    }

    protected function configureShopperLogos(): void
    {
        // Komerce assets/illustration PNG — exact filenames verified against GCS bucket
        $komerceIllustrationBase = 'https://storage.googleapis.com/komerce/assets/illustration/';

        /** @var array<string,string> slug → GCS filename (case-sensitive) */
        $carrierLogoMap = [
            'jnt' => 'JNT.png',
            'jne' => 'jne.png',
            'sicepat' => 'sicepat.png',
            'ide' => null, // no illustration found; fallback to rajaongkir SVG
            'anteraja' => null,
            'pos' => null,
            'tiki' => null,
            'lion' => 'lion-parcel.png',
            'ninja' => 'NINJA.png',
            'wahana' => null,
            'rpx' => null,
            'ncs' => null,
        ];

        $this->app->bind('shopper.carrier.logo', static function () use ($komerceIllustrationBase, $carrierLogoMap): \Closure {
            return static function (Carrier $carrier) use ($komerceIllustrationBase, $carrierLogoMap): ?string {
                $dbLogo = data_get($carrier->metadata, 'logo_url') ?? data_get($carrier->metadata, 'logo');
                if (is_string($dbLogo) && $dbLogo !== '') {
                    return Str::startsWith($dbLogo, ['http://', 'https://']) ? $dbLogo : asset($dbLogo);
                }

                $slug = strtolower((string) $carrier->slug);
                $cdnLogo = KomerceCourierAssets::logoUrl($slug);
                if ($cdnLogo !== null) {
                    return $cdnLogo;
                }

                // Prefer Komerce assets/illustration PNG (exact, verified URLs)
                if (array_key_exists($slug, $carrierLogoMap) && $carrierLogoMap[$slug] !== null) {
                    return $komerceIllustrationBase.$carrierLogoMap[$slug];
                }

                // Fallback: Komerce rajaongkir SVG bucket
                if (in_array($slug, ['jne', 'sicepat', 'ide', 'anteraja', 'pos', 'tiki', 'lion', 'ninja', 'wahana', 'rpx', 'ncs'], true)) {
                    return "https://storage.googleapis.com/komerce/rajaongkir/{$slug}.svg";
                }

                // Fallback: local public/images/couriers/
                if (file_exists(public_path("images/couriers/{$slug}.svg"))) {
                    $mtime = filemtime(public_path("images/couriers/{$slug}.svg"));

                    return asset("images/couriers/{$slug}.svg")."?v={$mtime}";
                }
                if (file_exists(public_path("images/couriers/{$slug}.png"))) {
                    $mtime = filemtime(public_path("images/couriers/{$slug}.png"));

                    return asset("images/couriers/{$slug}.png")."?v={$mtime}";
                }

                return null;
            };
        });

        $this->app->bind('shopper.payment.logo', static function (): \Closure {
            return static function (PaymentMethod $method): ?string {
                $logo = data_get($method->metadata, 'logo');
                if (is_string($logo) && $logo !== '') {
                    return asset($logo);
                }

                $channel = data_get($method->metadata, 'channel_code');
                $slug = strtolower((string) ($channel ?: $method->slug));
                $slug = str_replace(['komerce-va-', 'komerce-'], '', $slug);

                if (file_exists(public_path("images/payments/{$slug}.svg"))) {
                    $mtime = filemtime(public_path("images/payments/{$slug}.svg"));

                    return asset("images/payments/{$slug}.svg")."?v={$mtime}";
                }
                if (file_exists(public_path("images/payments/{$slug}.png"))) {
                    $mtime = filemtime(public_path("images/payments/{$slug}.png"));

                    return asset("images/payments/{$slug}.png")."?v={$mtime}";
                }

                return null;
            };
        });
    }
}
