<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Payment\Adapters\KomercePaymentAdapter;
use App\Domain\Payment\Contracts\PaymentDriverContract;
use App\Domain\Shipping\Adapters\RajaOngkirShippingAdapter;
use App\Domain\Shipping\Contracts\ShippingDriverContract;
use App\Livewire\Shopper\KomerceOrderShipping;
use App\Models\OrderShipment;
use App\Models\User;
use App\Observers\InventoryObserver;
use App\Observers\OrderObserver;
use App\Observers\OrderShipmentObserver;
use Shopper\Core\Models\Inventory;
use App\Payment\KomerceDriver;
use App\Shipping\Drivers\KomerceShippingDriver;
use App\Shipping\Drivers\RajaOngkirDriver;
use App\Stock\AllocationPlanStockAllocator;
use App\Support\CheckoutAllocationContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Livewire\Livewire;
use Shopper\Core\Contracts\StockAllocator;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
use Shopper\Shipping\Facades\Shipping;
use Shopper\View\OrderRenderHook;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CheckoutAllocationContext::class);
        $this->app->bind(StockAllocator::class, AllocationPlanStockAllocator::class);
        $this->app->bind(PaymentDriverContract::class, KomercePaymentAdapter::class);
        $this->app->bind(ShippingDriverContract::class, RajaOngkirShippingAdapter::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Order::observe(OrderObserver::class);
        OrderShipment::observe(OrderShipmentObserver::class);
        Inventory::observe(InventoryObserver::class);

        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureShopperPayment();
        $this->configureShopperShipping();
        $this->configureShopperLogos();
        $this->configureShopperFulfillment();
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
        Payment::extend('komerce', static fn (): KomerceDriver => new KomerceDriver);

        config()->set('shopper.payment.drivers.komerce.enabled', true);
    }

    protected function configureShopperShipping(): void
    {
        Shipping::extend('rajaongkir', static fn (): RajaOngkirDriver => new RajaOngkirDriver);
        Shipping::extend('komerce', static fn (): KomerceShippingDriver => new KomerceShippingDriver);

        config()->set('shopper.shipping.drivers.rajaongkir.enabled', true);
        config()->set('shopper.shipping.drivers.komerce.enabled', true);
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
                    return \Illuminate\Support\Str::startsWith($dbLogo, ['http://', 'https://']) ? $dbLogo : asset($dbLogo);
                }

                $slug = strtolower((string) $carrier->slug);
                $cdnLogo = \App\Support\KomerceCourierAssets::logoUrl($slug);
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
                    return asset("images/couriers/{$slug}.svg");
                }
                if (file_exists(public_path("images/couriers/{$slug}.png"))) {
                    return asset("images/couriers/{$slug}.png");
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
                    return asset("images/payments/{$slug}.svg");
                }
                if (file_exists(public_path("images/payments/{$slug}.png"))) {
                    return asset("images/payments/{$slug}.png");
                }

                return null;
            };
        });
    }

    protected function configureShopperFulfillment(): void
    {
        Livewire::component('komerce-order-shipping', KomerceOrderShipping::class);
        Livewire::component('shopper-order-summary', \App\Livewire\Shopper\OrderSummary::class);
        Livewire::component('shopper-slide-overs.create-shipping-label', \App\Livewire\Shopper\SlideOvers\CreateShippingLabel::class);
        Livewire::component('shopper-order-customer', \App\Livewire\Shopper\OrderCustomer::class);

        $resolveOrder = static function (): ?Order {
            $routeOrder = request()->route('order') ?? request()->route('id');

            if (! $routeOrder && preg_match('#/orders/([^/]+)/detail#', request()->path(), $matches)) {
                $routeOrder = urldecode($matches[1]);
            }

            if (! $routeOrder && request()->header('referer')) {
                if (preg_match('#/orders/([^/]+)/detail#', (string) request()->header('referer'), $matches)) {
                    $routeOrder = urldecode($matches[1]);
                }
            }

            return match (true) {
                $routeOrder instanceof Order => $routeOrder,
                is_numeric($routeOrder) => Order::query()->find($routeOrder),
                is_string($routeOrder) && $routeOrder !== '' => Order::query()->where('number', $routeOrder)->orWhere('id', $routeOrder)->first(),
                default => null,
            };
        };

        shopper()->renderHook(
            OrderRenderHook::DETAIL_MAIN_BEFORE,
            static function () use ($resolveOrder): string {
                $order = $resolveOrder();

                if (! $order instanceof Order) {
                    return '';
                }

                return view('shopper.partials.komerce-order-shipping-hook', [
                    'order' => $order,
                ])->render();
            },
        );
    }
}
