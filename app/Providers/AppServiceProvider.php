<?php

declare(strict_types=1);

namespace App\Providers;

use App\Livewire\Shopper\KomerceOrderShipping;
use App\Models\User;
use App\Payment\KomerceDriver;
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
use Shopper\Core\Models\Order;
use Shopper\Payment\Facades\Payment;
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
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureAuthorization();
        $this->configureShopperPayment();
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
            static fn (User $user, ?Order $order = null): bool => $user->isAdmin(),
        );

        Gate::define(
            'print-shipment-label',
            static fn (User $user, ?Order $order = null): bool => $user->isAdmin(),
        );
    }

    protected function configureShopperPayment(): void
    {
        Payment::extend('komerce', static fn (): KomerceDriver => new KomerceDriver);

        config()->set('shopper.payment.drivers.komerce.enabled', true);
    }

    protected function configureShopperFulfillment(): void
    {
        Livewire::component('komerce-order-shipping', KomerceOrderShipping::class);

        shopper()->renderHook(
            OrderRenderHook::DETAIL_MAIN_AFTER,
            static function (): string {
                $order = request()->route('order');

                if (! $order instanceof Order) {
                    return '';
                }

                if (! auth()->user()?->isAdmin()) {
                    return '';
                }

                return view('shopper.partials.komerce-order-shipping-hook', [
                    'order' => $order,
                ])->render();
            },
        );
    }
}
