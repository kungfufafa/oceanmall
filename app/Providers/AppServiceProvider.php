<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Stock\AllocationPlanStockAllocator;
use App\Support\CheckoutAllocationContext;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Shopper\Core\Contracts\StockAllocator;
use Shopper\Core\Models\Order;

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
}
