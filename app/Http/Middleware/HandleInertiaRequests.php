<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\GetCountriesByZone;
use App\Actions\ZoneSessionManager;
use App\Models\Channel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Models\Category;
use Shopper\Core\Models\PaymentMethod;

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
     * Payment logos shown in the storefront footer (enabled commerce methods only).
     *
     * @return array<int, array{key: string, title: string, logo: string|null, driver: string}>
     */
    private function footerPaymentMethods(): array
    {
        $stripeEnabled = (bool) config('shopper.payment.drivers.stripe.enabled', false);
        $komerceEnabled = komerce_enabled();

        return Cache::remember(
            'footer.payment_methods.v3.'.($stripeEnabled ? '1' : '0').'.'.($komerceEnabled ? '1' : '0'),
            600,
            function () use ($stripeEnabled, $komerceEnabled): array {
                $methods = PaymentMethod::query()
                    ->where('is_enabled', true)
                    ->orderBy('id')
                    ->get()
                    ->filter(function (PaymentMethod $method) use ($stripeEnabled, $komerceEnabled): bool {
                        return match ($method->driver ?? 'manual') {
                            'stripe' => $stripeEnabled,
                            'komerce' => $komerceEnabled,
                            'manual' => true,
                            default => false,
                        };
                    });

                $badges = [];

                foreach ($methods as $method) {
                    $driver = (string) ($method->driver ?? 'manual');
                    $meta = $this->decodePaymentMetadata($method->metadata);

                    if ($driver === 'manual') {
                        $badges[] = [
                            'key' => 'cod',
                            'title' => 'Bayar di Tempat (COD)',
                            'logo' => $this->commerceLogo('cod.svg'),
                            'driver' => 'manual',
                        ];

                        continue;
                    }

                    if ($driver === 'stripe') {
                        $badges[] = [
                            'key' => 'stripe',
                            'title' => 'Kartu (Stripe)',
                            'logo' => $this->commerceLogo('stripe.svg'),
                            'driver' => 'stripe',
                        ];

                        continue;
                    }

                    if ($driver === 'komerce') {
                        foreach ($this->komercePaymentBadges($meta) as $badge) {
                            $badges[] = $badge;
                        }
                    }
                }

                $seen = [];

                return collect($badges)
                    ->filter(function (array $badge) use (&$seen): bool {
                        if (isset($seen[$badge['key']])) {
                            return false;
                        }

                        $seen[$badge['key']] = true;

                        return true;
                    })
                    ->values()
                    ->all();
            },
        );
    }

    /**
     * @param  array<string, mixed>  $meta
     * @return array<int, array{key: string, title: string, logo: string|null, driver: string}>
     */
    private function komercePaymentBadges(array $meta): array
    {
        $paymentType = isset($meta['payment_type']) ? strtolower((string) $meta['payment_type']) : null;
        $channelCode = isset($meta['channel_code']) ? strtoupper((string) $meta['channel_code']) : null;

        $banks = [
            'BCA' => ['key' => 'bca', 'title' => 'BCA', 'file' => 'bca.png'],
            'BNI' => ['key' => 'bni', 'title' => 'BNI', 'file' => 'bni.png'],
            'BRI' => ['key' => 'bri', 'title' => 'BRI', 'file' => 'bri.png'],
            'MANDIRI' => ['key' => 'mandiri', 'title' => 'Mandiri', 'file' => 'mandiri.png'],
            'PERMATA' => ['key' => 'permata', 'title' => 'Permata', 'file' => 'permata.png'],
            'CIMB' => ['key' => 'cimb', 'title' => 'CIMB', 'file' => 'cimb.png'],
            'BSI' => ['key' => 'bsi', 'title' => 'BSI', 'file' => 'bsi.png'],
        ];

        $channelAliases = [
            'BCA' => 'BCA',
            'BCAVA' => 'BCA',
            'BNI' => 'BNI',
            'BNIVA' => 'BNI',
            'BRI' => 'BRI',
            'BRIVA' => 'BRI',
            'MANDIRI' => 'MANDIRI',
            'MANDIRIVA' => 'MANDIRI',
            'PERMATA' => 'PERMATA',
            'CIMB' => 'CIMB',
            'BSI' => 'BSI',
        ];

        if ($paymentType === 'qris') {
            return [[
                'key' => 'qris',
                'title' => 'QRIS',
                'logo' => $this->commerceLogo('qris.png'),
                'driver' => 'komerce',
            ]];
        }

        if ($channelCode !== null && $channelCode !== '') {
            $bankKey = $channelAliases[$channelCode] ?? $channelCode;
            $bank = $banks[$bankKey] ?? null;

            if ($bank === null) {
                return [[
                    'key' => 'va-'.strtolower($channelCode),
                    'title' => 'VA '.$channelCode,
                    'logo' => null,
                    'driver' => 'komerce',
                ]];
            }

            return [[
                'key' => $bank['key'],
                'title' => $bank['title'],
                'logo' => $this->commerceLogo($bank['file']),
                'driver' => 'komerce',
            ]];
        }

        // Generic VA / QRIS method: show the channels Komerce Payment API supports.
        $badges = [[
            'key' => 'qris',
            'title' => 'QRIS',
            'logo' => $this->commerceLogo('qris.png'),
            'driver' => 'komerce',
        ]];

        foreach ($banks as $bank) {
            $badges[] = [
                'key' => $bank['key'],
                'title' => $bank['title'],
                'logo' => $this->commerceLogo($bank['file']),
                'driver' => 'komerce',
            ];
        }

        return $badges;
    }

    /**
     * Shipping courier logos configured for RajaOngkir / Komerce.
     *
     * @return array<int, array{code: string, label: string, logo: string|null}>
     */
    private function footerShippingCouriers(): array
    {
        if (! komerce_enabled()) {
            return [];
        }

        $labels = [
            'jne' => 'JNE',
            'jnt' => 'J&T Express',
            'sicepat' => 'SiCepat',
            'pos' => 'Pos Indonesia',
            'anteraja' => 'AnterAja',
            'tiki' => 'TIKI',
            'wahana' => 'Wahana',
            'ninja' => 'Ninja Xpress',
            'lion' => 'Lion Parcel',
            'ide' => 'ID Express',
            'sap' => 'SAP Express',
            'rex' => 'REX',
            'sentral' => 'Sentral Cargo',
            'j&t' => 'J&T Express',
        ];

        /** @var list<string> $configured */
        $configured = config('komerce.couriers', []);

        return collect($configured)
            ->filter(fn (mixed $code): bool => is_string($code) && $code !== '')
            ->map(function (string $code) use ($labels): array {
                $key = strtolower($code);
                $normalized = $key === 'j&t' ? 'jnt' : $key;

                return [
                    'code' => $normalized,
                    'label' => $labels[$key] ?? $labels[$normalized] ?? strtoupper($code),
                    'logo' => $this->courierLogoUrl($normalized),
                ];
            })
            ->unique('code')
            ->values()
            ->all();
    }

    /**
     * Prefer local full-color logos. RajaOngkir CDN SVGs ship with opacity="0.8"
     * which makes the marks look washed out on a white footer.
     */
    private function courierLogoUrl(string $code): ?string
    {
        $local = [
            'jne' => 'jne.svg',
            'jnt' => 'jnt.svg',
            'sicepat' => 'sicepat.svg',
        ];

        if (isset($local[$code])) {
            return $this->commerceLogo($local[$code]);
        }

        $cdnCodes = [
            'pos',
            'anteraja',
            'tiki',
            'wahana',
            'ninja',
            'lion',
            'sap',
            'rex',
        ];

        if (in_array($code, $cdnCodes, true)) {
            return 'https://storage.googleapis.com/komerce/rajaongkir/'.$code.'.svg';
        }

        return null;
    }

    private function commerceLogo(string $file): string
    {
        return asset('images/commerce/'.$file);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodePaymentMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (is_string($metadata) && $metadata !== '' && str_starts_with($metadata, '{')) {
            $decoded = json_decode($metadata, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
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
