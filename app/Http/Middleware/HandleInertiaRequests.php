<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\GetCountriesByZone;
use App\Actions\ZoneSessionManager;
use App\Models\Channel;
use App\Support\EnabledCarriers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Models\Carrier;
use Shopper\Core\Models\Category;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Services\PaymentProcessingService;

class HandleInertiaRequests extends Middleware
{
    /**
     * @var string
     */
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user()?->append('full_name'),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'info' => fn () => $request->session()->get('info'),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'shop' => fn (): array => $this->shopProps(),
            'notificationsUnreadCount' => fn (): int => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function shopProps(): array
    {
        $cart = resolve(CartSessionManager::class)->current();
        $zone = ZoneSessionManager::ensureSession();

        return [
            'cart_count' => $cart?->lines->sum('quantity') ?? 0,
            'zone' => $zone ? [
                'country_code' => $zone->countryCode,
                'country_name' => $zone->countryName,
                'currency_code' => $zone->currencyCode,
                'zone_id' => $zone->zoneId,
            ] : null,
            'currency' => current_currency(),
            'channels' => Channel::query()
                ->scopes('enabled')
                ->select('id', 'name', 'slug')
                ->get()
                ->toArray(),
            'available_zones' => fn (): array => resolve(GetCountriesByZone::class)->handle()->values()->toArray(),
            'tax_label' => current_tax_label(),
            'nav_categories' => $this->topCategories(4, 'nav'),
            'footer_categories' => $this->topCategories(6, 'footer'),
            'payment_methods' => $this->footerPaymentMethods(),
            'shipping_couriers' => $this->footerShippingCouriers(),
        ];
    }

    /**
     * Payment logos shown in the storefront footer (enabled cpanel payment methods).
     *
     * @return array<int, array{key: string, title: string, logo: string|null, driver: string}>
     */
    private function footerPaymentMethods(): array
    {
        $stripeEnabled = (bool) config('shopper.payment.drivers.stripe.enabled', false);
        $komerceEnabled = komerce_enabled();

        return Cache::remember(
            'footer.payment_methods.v4.'.$this->commerceCatalogSignature($stripeEnabled, $komerceEnabled),
            600,
            function () use ($stripeEnabled, $komerceEnabled): array {
                $service = resolve(PaymentProcessingService::class);

                return PaymentMethod::query()
                    ->where('is_enabled', true)
                    ->orderBy('id')
                    ->get()
                    ->filter(fn (PaymentMethod $method): bool => $this->paymentMethodAvailable($method, $stripeEnabled, $komerceEnabled))
                    ->map(function (PaymentMethod $method) use ($service): array {
                        try {
                            $logo = $service->getLogoUrl($method);
                        } catch (\InvalidArgumentException) {
                            $logo = null;
                        }

                        return [
                            'key' => (string) $method->slug,
                            'title' => (string) $method->title,
                            'logo' => $logo,
                            'driver' => (string) ($method->driver ?? 'manual'),
                        ];
                    })
                    ->values()
                    ->all();
            },
        );
    }

    private function paymentMethodAvailable(PaymentMethod $method, bool $stripeEnabled, bool $komerceEnabled): bool
    {
        return match ($method->driver ?? 'manual') {
            'stripe' => $stripeEnabled,
            'komerce' => $komerceEnabled,
            default => Payment::isConfigured($method->driver ?? 'manual'),
        };
    }

    /**
     * Shipping courier logos from enabled cpanel carriers.
     *
     * @return array<int, array{code: string, label: string, logo: string|null}>
     */
    private function footerShippingCouriers(): array
    {
        $komerceEnabled = komerce_enabled();
        /** @var \Closure(Carrier): ?string $logoResolver */
        $logoResolver = app('shopper.carrier.logo');

        return Cache::remember(
            'footer.shipping_couriers.v2.'.$this->commerceCatalogSignature(
                (bool) config('shopper.payment.drivers.stripe.enabled', false),
                $komerceEnabled,
            ),
            600,
            function () use ($komerceEnabled, $logoResolver): array {
                return EnabledCarriers::forDisplay()
                    ->filter(function (Carrier $carrier) use ($komerceEnabled): bool {
                        if (($carrier->driver ?? 'manual') === 'manual') {
                            return true;
                        }

                        return $komerceEnabled;
                    })
                    ->map(function (Carrier $carrier) use ($logoResolver): array {
                        $code = strtolower((string) $carrier->slug);

                        return [
                            'code' => $code,
                            'label' => (string) $carrier->name,
                            'logo' => $logoResolver($carrier),
                        ];
                    })
                    ->values()
                    ->all();
            },
        );
    }

    private function commerceCatalogSignature(bool $stripeEnabled, bool $komerceEnabled): string
    {
        $paymentIds = PaymentMethod::query()
            ->where('is_enabled', true)
            ->orderBy('id')
            ->pluck('id')
            ->implode(',');

        $carrierSlugs = EnabledCarriers::forDisplay()
            ->pluck('slug')
            ->map(static fn (mixed $slug): string => strtolower((string) $slug))
            ->implode(',');

        return ($stripeEnabled ? '1' : '0')
            .'.'.($komerceEnabled ? '1' : '0')
            .'.'.$paymentIds
            .'.'.$carrierSlugs;
    }

    /**
     * @return array<int, array{id: int, name: string, slug: string}>
     */
    private function topCategories(int $limit, string $cacheKey): array
    {
        return Cache::remember(
            "{$cacheKey}.categories.".app()->getLocale().".{$limit}",
            7200,
            fn (): array => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->take($limit)
                ->get(['id', 'name', 'slug'])
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->all(),
        );
    }
}
