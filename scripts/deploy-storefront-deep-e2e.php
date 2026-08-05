<?php

declare(strict_types=1);

/**
 * Deep deploy-targeted HTTP E2E auditor for OceanMall storefront.
 *
 * Broad read + mutation coverage across guest/auth/catalog/cart/checkout surfaces.
 * Outputs actionable weakness report (severity-tagged findings).
 *
 * Modes (DEPLOY_E2E_MODE):
 *   audit (default) — full coverage WITHOUT placing an order
 *
 * Optional order placement:
 *   DEPLOY_E2E_PLACE_ORDER=YES — also place unpaid order at end
 *   (non-local hosts also require DEPLOY_E2E_CONFIRM=YES)
 *
 * Usage:
 *   DEPLOY_BASE_URL=https://staging.example.com \
 *   DEPLOY_E2E_EMAIL=customer@oceanmall.test \
 *   DEPLOY_E2E_PASSWORD=password123 \
 *   php scripts/deploy-storefront-deep-e2e.php
 *
 * Does NOT print secrets. Exit 0 unless a hard HTTP/assertion fail.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$base = rtrim((string) (getenv('DEPLOY_BASE_URL') ?: getenv('UAT_BASE_URL') ?: ''), '/');
$email = (string) (getenv('DEPLOY_E2E_EMAIL') ?: 'customer@oceanmall.test');
$password = (string) (getenv('DEPLOY_E2E_PASSWORD') ?: 'password123');
$mode = strtolower((string) (getenv('DEPLOY_E2E_MODE') ?: 'audit'));
$confirm = strtoupper((string) (getenv('DEPLOY_E2E_CONFIRM') ?: ''));
$placeOrder = strtoupper((string) (getenv('DEPLOY_E2E_PLACE_ORDER') ?: '')) === 'YES';
$productSlugHint = trim((string) (getenv('DEPLOY_E2E_PRODUCT_SLUG') ?: ''));
$destQuery = trim((string) (getenv('DEPLOY_E2E_DESTINATION') ?: 'Jakarta Selatan'));
$couponCode = strtoupper(trim((string) (getenv('DEPLOY_E2E_COUPON') ?: 'OCEAN10')));

$slowThresholdMs = 3000;

if ($mode !== 'audit') {
    fwrite(STDERR, "FAIL: DEPLOY_E2E_MODE for this script must be audit (got {$mode})\n");
    exit(1);
}

if ($base === '') {
    fwrite(STDERR, "FAIL: set DEPLOY_BASE_URL (e.g. https://shop.example.com)\n");
    exit(1);
}

$host = parse_url($base, PHP_URL_HOST) ?: '';
$isLocal = in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
    || str_ends_with((string) $host, '.test')
    || str_ends_with((string) $host, '.local');

if ($placeOrder && ! $isLocal && $confirm !== 'YES') {
    fwrite(STDERR, "FAIL: non-local place-order requires DEPLOY_E2E_CONFIRM=YES\n");
    exit(1);
}

$steps = [];
$weaknesses = [];
$summary = ['passed' => 0, 'warned' => 0, 'failed' => 0, 'slow' => 0];

$emitJson = static function (bool $ok) use ($base, $mode, &$steps, &$weaknesses, &$summary): never {
    echo json_encode([
        'ok' => $ok,
        'base' => $base,
        'mode' => $mode,
        'place_order' => getenv('DEPLOY_E2E_PLACE_ORDER') ?: 'NO',
        'summary' => $summary,
        'weaknesses' => $weaknesses,
        'steps' => $steps,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit($ok ? 0 : 1);
};

$ok = static function (string $step, string $detail = '', ?int $ms = null) use (&$steps, &$summary): void {
    $entry = ['step' => $step, 'ok' => true, 'detail' => $detail];
    if ($ms !== null) {
        $entry['ms'] = $ms;
    }
    $steps[] = $entry;
    $summary['passed']++;
    $suffix = $detail !== '' ? " — $detail" : '';
    if ($ms !== null) {
        $suffix .= " ({$ms}ms)";
    }
    echo "OK   [$step]{$suffix}\n";
};

$warn = static function (string $step, string $detail, ?int $ms = null) use (&$steps, &$summary): void {
    $entry = ['step' => $step, 'ok' => true, 'warn' => true, 'detail' => $detail];
    if ($ms !== null) {
        $entry['ms'] = $ms;
    }
    $steps[] = $entry;
    $summary['warned']++;
    $suffix = $detail;
    if ($ms !== null) {
        $suffix .= " ({$ms}ms)";
    }
    echo "WARN [$step] — {$suffix}\n";
};

$fail = static function (string $step, string $detail) use (&$steps, &$summary, $emitJson): never {
    $steps[] = ['step' => $step, 'ok' => false, 'detail' => $detail];
    $summary['failed']++;
    fwrite(STDERR, "FAIL [$step]: $detail\n");
    $emitJson(false);
};

$weakness = static function (
    string $severity,
    string $area,
    string $finding,
    string $hint = '',
    ?string $step = null,
) use (&$weaknesses, $warn): void {
    $weaknesses[] = [
        'severity' => $severity,
        'area' => $area,
        'finding' => $finding,
        'hint' => $hint,
    ];
    if ($step !== null) {
        $warn($step, "[{$severity}] {$finding}".($hint !== '' ? " — {$hint}" : ''));
    }
};

$cookieJar = new \GuzzleHttp\Cookie\CookieJar;

$makeClient = static function () use ($base, $cookieJar) {
    return Http::baseUrl($base)
        ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => false])
        ->timeout(90)
        ->acceptJson();
};

$xsrf = '';
$inertiaVersion = '';

$refreshXsrf = static function () use ($cookieJar, &$xsrf): void {
    foreach ($cookieJar->toArray() as $cookie) {
        if (($cookie['Name'] ?? '') === 'XSRF-TOKEN') {
            $xsrf = urldecode((string) $cookie['Value']);
        }
    }
};

$send = static function (
    string $method,
    string $path,
    array $options = [],
    bool $inertia = true,
) use ($makeClient, $refreshXsrf, &$xsrf, &$inertiaVersion, $base) {
    $attempt = static function () use ($makeClient, $method, $path, $options, $inertia, &$xsrf, &$inertiaVersion, $base) {
        $headers = [
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'text/html, application/xhtml+xml',
            'Referer' => $options['referer'] ?? ($base.'/'),
        ];
        if ($xsrf !== '') {
            $headers['X-XSRF-TOKEN'] = $xsrf;
        }
        if ($inertia) {
            $headers['X-Inertia'] = 'true';
            $headers['X-Inertia-Version'] = $inertiaVersion;
        }
        $headers = array_merge($headers, $options['headers'] ?? []);

        $pending = $makeClient()->withHeaders($headers);

        return match (strtoupper($method)) {
            'GET' => $pending->get($path, $options['query'] ?? []),
            'POST' => isset($options['json'])
                ? $pending->asJson()->post($path, $options['json'])
                : $pending->asForm()->post($path, $options['form'] ?? []),
            'PATCH' => isset($options['json'])
                ? $pending->asJson()->patch($path, $options['json'])
                : $pending->asForm()->patch($path, $options['form'] ?? []),
            'DELETE' => $pending->delete($path),
            default => throw new InvalidArgumentException("Unsupported method {$method}"),
        };
    };

    $response = $attempt();
    $refreshXsrf();

    if ($inertia && $response->status() === 409) {
        $headerVersion = $response->header('X-Inertia-Version');
        if (is_string($headerVersion) && $headerVersion !== '') {
            $inertiaVersion = $headerVersion;
            $response = $attempt();
            $refreshXsrf();
        }
    }

    return $response;
};

$request = static function (string $method, string $path, array $options = []) use ($send) {
    return $send($method, $path, $options, true);
};

$htmlRequest = static function (string $method, string $path, array $options = []) use ($send) {
    return $send($method, $path, $options, false);
};

$assertHttp = static function ($response, string $step, array $allowed = [200, 302, 303]) use ($fail): void {
    if (! in_array($response->status(), $allowed, true)) {
        $fail($step, 'HTTP '.$response->status().' '.substr($response->body(), 0, 400));
    }
};

$propsOf = static function ($response): array {
    $json = $response->json();
    if (! is_array($json)) {
        return [];
    }

    return is_array($json['props'] ?? null) ? $json['props'] : [];
};

$componentOf = static function ($response): ?string {
    $json = $response->json();
    if (! is_array($json)) {
        return null;
    }
    $component = $json['component'] ?? null;

    return is_string($component) && $component !== '' ? $component : null;
};

$timed = static function (callable $fn) use ($slowThresholdMs, &$summary, $warn): array {
    $start = hrtime(true);
    $response = $fn();
    $ms = (int) round((hrtime(true) - $start) / 1_000_000);

    return ['response' => $response, 'ms' => $ms];
};

$checkSlow = static function (string $step, int $ms) use ($slowThresholdMs, $warn, &$summary): void {
    if ($ms > $slowThresholdMs) {
        $summary['slow']++;
        $warn($step.'_slow', "response {$ms}ms > {$slowThresholdMs}ms threshold", $ms);
    }
};

$extractProducts = static function (array $props): array {
    $products = data_get($props, 'products.data');
    if (! is_array($products) || $products === []) {
        $products = data_get($props, 'products') ?? [];
        if (isset($products['data']) && is_array($products['data'])) {
            $products = $products['data'];
        }
    }

    return is_array($products) ? $products : [];
};

$auditShopProps = static function (array $props, string $page, string $step) use ($weakness): void {
    $shop = $props['shop'] ?? null;
    if (! is_array($shop)) {
        $weakness('high', 'architecture', "Missing shared shop props on {$page}", 'Check HandleInertiaRequests::share', $step);

        return;
    }

    $nav = $shop['nav_categories'] ?? null;
    if (! is_array($nav)) {
        $weakness('medium', 'navigation', "shop.nav_categories missing on {$page}", 'Ensure categories are seeded and enabled', $step.'_nav');
    } elseif ($nav === []) {
        $weakness('low', 'navigation', "shop.nav_categories empty on {$page}", 'Add top-level categories for nav', $step.'_nav');
    }

    $payments = $shop['payment_methods'] ?? null;
    if (! is_array($payments)) {
        $weakness('medium', 'footer', "shop.payment_methods missing on {$page}", 'Enable payment methods in admin', $step.'_payments');
    } elseif ($payments === []) {
        $weakness('low', 'footer', "shop.payment_methods empty on {$page}", 'Configure COD/Komerce/Stripe badges', $step.'_payments');
    }

    if (! array_key_exists('cart_count', $shop)) {
        $weakness('medium', 'cart', "shop.cart_count missing on {$page}", 'Cart badge may not render', $step.'_cart_count');
    }
};

$assertInertiaComponent = static function ($response, string $step, ?string $expectedPrefix = null) use ($componentOf, $weakness): void {
    $component = $componentOf($response);
    if ($component === null) {
        $weakness('medium', 'inertia', "No Inertia component on {$step} JSON response", 'Verify X-Inertia header and middleware', $step.'_component');
    } elseif ($expectedPrefix !== null && ! str_starts_with($component, $expectedPrefix)) {
        $weakness('low', 'inertia', "{$step} component={$component}, expected prefix {$expectedPrefix}", 'Route may render wrong page', $step.'_component');
    }
};

$cartLines = static function (array $cartProps): array {
    $lines = data_get($cartProps, 'cart.lines');
    if (is_array($lines)) {
        return $lines;
    }

    return [];
};

echo "DEEP DEPLOY E2E base={$base} mode={$mode} place_order=".($placeOrder ? 'YES' : 'NO')." host={$host}\n";

// ── Guest surfaces ───────────────────────────────────────────────────────────
$guestGets = [
    'guest_home' => ['path' => '/', 'component' => 'shop/'],
    'guest_shop' => ['path' => '/shop', 'component' => 'shop/'],
    'guest_shop_sort_price_asc' => ['path' => '/shop?sort=price_asc', 'component' => 'shop/'],
    'guest_shop_sort_price_desc' => ['path' => '/shop?sort=price_desc', 'component' => 'shop/'],
    'guest_shop_sort_name' => ['path' => '/shop?sort=name', 'component' => 'shop/'],
    'guest_categories' => ['path' => '/categories', 'component' => 'shop/'],
    'guest_search_hit' => ['path' => '/search?q=realme', 'component' => 'shop/'],
    'guest_search_miss' => ['path' => '/search?q=zzzznomatch', 'component' => 'shop/'],
    'guest_cart' => ['path' => '/cart', 'component' => 'shop/'],
    'guest_login' => ['path' => '/login', 'component' => 'auth/'],
    'guest_register' => ['path' => '/register', 'component' => 'auth/'],
    'guest_forgot_password' => ['path' => '/forgot-password', 'component' => 'auth/'],
];

$homeProps = [];
$shopProps = [];
$categoriesProps = [];
$chosen = null;
$categorySlug = null;
$collectionSlug = null;

foreach ($guestGets as $name => $route) {
    $path = $route['path'];
    $componentPrefix = $route['component'];
    $timedResult = $timed(static fn () => $request('GET', $path));
    $res = $timedResult['response'];
    $ms = $timedResult['ms'];
    $assertHttp($res, $name);
    $checkSlow($name, $ms);

    $props = $propsOf($res);
    $assertInertiaComponent($res, $name, $componentPrefix);

    if ($name === 'guest_home') {
        $homeProps = $props;
        $auditShopProps($props, 'home', $name);

        $featured = data_get($props, 'featuredProducts', []);
        $promo = data_get($props, 'promoProducts', []);
        $collections = data_get($props, 'featuredCollections', []);
        $homeCategories = data_get($props, 'categories', []);

        $hasContent = (is_array($featured) && $featured !== [])
            || (is_array($promo) && $promo !== [])
            || (is_array($collections) && $collections !== [])
            || (is_array($homeCategories) && $homeCategories !== []);

        if (! $hasContent) {
            $weakness('high', 'home', 'Home page has no featured/promo/collection/category sections', 'Seed products and collections', $name.'_content');
        }

        if (is_array($collections) && $collections !== []) {
            $firstCollection = $collections[0];
            $collectionSlug = (string) ($firstCollection['slug'] ?? '');
        }
    }

    if ($name === 'guest_shop') {
        $shopProps = $props;
        $auditShopProps($props, 'shop', $name);
    }

    if ($name === 'guest_categories') {
        $categoriesProps = $props;
        $cats = data_get($props, 'categories', []);
        if (is_array($cats) && $cats !== []) {
            $categorySlug = (string) ($cats[0]['slug'] ?? '');
        }
    }

    if ($name === 'guest_search_miss') {
        $query = (string) data_get($props, 'query', '');
        $productsProp = array_key_exists('products', $props) ? $props['products'] : '__missing__';
        if ($productsProp === '__missing__') {
            $weakness('medium', 'search', 'Empty search missing products prop', 'SearchController should return paginator (may be empty)', $name);
        } elseif ($productsProp === null && mb_strlen($query) >= 2) {
            $weakness('medium', 'search', 'Empty search returned products=null', 'SearchController should paginate when q length >= 2', $name);
        } elseif (is_array($productsProp)) {
            $results = data_get($productsProp, 'data', $productsProp);
            if (is_array($results) && $results !== []) {
                $weakness('low', 'search', 'No-match search returned products', 'Verify search query isolation', $name);
            }
        }
    }

    $ok($name, 'HTTP '.$res->status(), $ms);
}

$cpanel = $makeClient()->get('/cpanel/login');
$refreshXsrf();
if (! in_array($cpanel->status(), [200, 302], true)) {
    $fail('guest_cpanel_login', 'HTTP '.$cpanel->status());
}
$ok('guest_cpanel_login', 'HTTP '.$cpanel->status());

// Seed CSRF via login page HTML
$loginPage = $htmlRequest('GET', '/login');
$assertHttp($loginPage, 'csrf_login_page', [200]);
if ($xsrf === '') {
    $fail('csrf', 'missing XSRF-TOKEN cookie before login');
}
$ok('csrf', 'token present');

if (preg_match('/data-page="([^"]+)"/', $loginPage->body(), $m)) {
    $decoded = html_entity_decode($m[1], ENT_QUOTES);
    $page = json_decode($decoded, true);
    if (is_array($page) && isset($page['version']) && is_string($page['version'])) {
        $inertiaVersion = $page['version'];
    }
}
$ok('inertia_version', $inertiaVersion !== '' ? 'from html' : 'will learn from 409');

// Discover product from shop props
$products = $extractProducts($shopProps);
if ($products === []) {
    $fail('catalog', 'no products on /shop');
}

if ($productSlugHint !== '') {
    foreach ($products as $p) {
        if (($p['slug'] ?? null) === $productSlugHint) {
            $chosen = $p;
            break;
        }
    }
}
if ($chosen === null) {
    foreach ($products as $p) {
        if (! empty($p['id'])) {
            $chosen = $p;
            break;
        }
    }
}
if ($chosen === null || empty($chosen['id']) || empty($chosen['slug'])) {
    $fail('catalog', 'could not pick product from shop');
}
$ok('catalog_pick', 'product='.$chosen['slug'].' id='.$chosen['id']);

if ($categorySlug !== null && $categorySlug !== '') {
    $catRes = $timed(static fn () => $request('GET', '/categories/'.$categorySlug));
    $assertHttp($catRes['response'], 'guest_category_show');
    $checkSlow('guest_category_show', $catRes['ms']);
    $assertInertiaComponent($catRes['response'], 'guest_category_show', 'shop/');
    $ok('guest_category_show', 'slug='.$categorySlug, $catRes['ms']);
} else {
    $weakness('medium', 'categories', 'No category slug to visit from /categories props', 'Seed enabled categories', 'guest_category_show');
}

if ($collectionSlug !== null && $collectionSlug !== '') {
    $colRes = $timed(static fn () => $request('GET', '/collections/'.$collectionSlug));
    $assertHttp($colRes['response'], 'guest_collection_show');
    $checkSlow('guest_collection_show', $colRes['ms']);
    $assertInertiaComponent($colRes['response'], 'guest_collection_show', 'shop/');
    $ok('guest_collection_show', 'slug='.$collectionSlug, $colRes['ms']);
} else {
    $weakness('low', 'collections', 'No featured collection on home to visit', 'Publish collections with products', 'guest_collection_show');
}

// ── PDP depth ────────────────────────────────────────────────────────────────
$pdpRes = $timed(static fn () => $request('GET', '/shop/'.$chosen['slug']));
$pdp = $pdpRes['response'];
$assertHttp($pdp, 'pdp');
$checkSlow('pdp', $pdpRes['ms']);
$pdpProps = $propsOf($pdp);
$assertInertiaComponent($pdp, 'pdp', 'shop/');
$auditShopProps($pdpProps, 'pdp', 'pdp');

$productId = (int) data_get($pdpProps, 'product.id', 0);
$productName = (string) data_get($pdpProps, 'product.name', '');
if ($productId < 1 || $productName === '') {
    $fail('pdp_props', 'missing product.id or product.name');
}

$hasPrice = filled(data_get($pdpProps, 'product.price'))
    || filled(data_get($pdpProps, 'product.formatted_price'))
    || filled(data_get($pdpProps, 'product.prices'))
    || filled(data_get($pdpProps, 'product.variants'));
if (! $hasPrice) {
    $weakness('high', 'catalog', "PDP {$chosen['slug']} has no visible price/variant data", 'Add prices for current currency zone', 'pdp_price');
}

$variantId = null;
$variantOptions = data_get($pdpProps, 'variantOptions');
if (is_array($variantOptions) && ($variantOptions['hasStructuredAttributes'] ?? false)) {
    $index = $variantOptions['variantIndex'] ?? [];
    if (! is_array($index) || $index === []) {
        $weakness('high', 'variants', "Product {$chosen['slug']} has structured attributes but empty variantIndex", 'Rebuild variant index in BuildVariantOptions', 'pdp_variant_index');
    } else {
        $variantId = (int) reset($index);
    }
}

$related = data_get($pdpProps, 'product.related_products', []);
if (is_array($related) && $related !== []) {
    $relatedSlug = (string) ($related[0]['slug'] ?? '');
    if ($relatedSlug !== '') {
        $relRes = $timed(static fn () => $request('GET', '/shop/'.$relatedSlug));
        $assertHttp($relRes['response'], 'pdp_related');
        $checkSlow('pdp_related', $relRes['ms']);
        $ok('pdp_related', 'slug='.$relatedSlug, $relRes['ms']);
    }
} else {
    $weakness('low', 'catalog', 'No related products on PDP', 'Link related products in admin', 'pdp_related');
}

$ok('pdp', 'product_id='.$productId.($variantId ? " variant_id={$variantId}" : ''), $pdpRes['ms']);

// ── Auth surfaces ────────────────────────────────────────────────────────────
$htmlRequest('GET', '/login');
if ($xsrf === '') {
    $fail('csrf_refresh', 'XSRF missing before login POST');
}

$login = $htmlRequest('POST', '/login', [
    'form' => [
        'email' => $email,
        'password' => $password,
    ],
    'referer' => $base.'/login',
]);
$assertHttp($login, 'auth_login');
if ($xsrf === '') {
    $fail('csrf_after_login', 'missing XSRF-TOKEN after login');
}
$ok('auth_login', 'HTTP '.$login->status().' as '.$email);

$zone = $htmlRequest('PATCH', '/zone', [
    'form' => ['country_code' => 'ID'],
    'referer' => $base.'/',
]);
$assertHttp($zone, 'auth_zone');
$ok('auth_zone', 'HTTP '.$zone->status().' country=ID');

$authGets = [
    'auth_dashboard' => ['/dashboard', 'dashboard'],
    'auth_settings_profile' => ['/settings/profile', 'settings/'],
    'auth_settings_security' => ['/settings/security', 'settings/'],
    'auth_account_orders' => ['/account/orders', null],
    'auth_account_notifications' => ['/account/notifications', null],
    'auth_account_addresses' => ['/account/addresses', null],
];

// Security settings may require recent password confirmation (Fortify 2FA).
$confirmPw = $htmlRequest('POST', '/user/confirm-password', [
    'form' => ['password' => $password],
    'referer' => $base.'/settings/security',
]);
if (in_array($confirmPw->status(), [200, 201, 302, 303], true)) {
    $ok('auth_password_confirm', 'HTTP '.$confirmPw->status());
} else {
    $weakness('medium', 'auth', 'Password confirmation failed (HTTP '.$confirmPw->status().')', 'Security page may return 423', 'auth_password_confirm');
}

foreach ($authGets as $name => [$path, $componentPrefix]) {
    $timedResult = $timed(static fn () => $request('GET', $path));
    $res = $timedResult['response'];
    $ms = $timedResult['ms'];

    if ($name === 'auth_settings_security' && $res->status() === 423) {
        $weakness('high', 'auth', 'Settings security returns 423 without password confirmation', 'POST /user/confirm-password before visit', $name);
        $warn($name, 'HTTP 423 — password confirmation required', $ms);
        continue;
    }

    $assertHttp($res, $name);
    $checkSlow($name, $ms);
    $props = $propsOf($res);
    if ($componentPrefix !== null) {
        $assertInertiaComponent($res, $name, $componentPrefix);
    } else {
        $assertInertiaComponent($res, $name);
    }
    if (in_array($name, ['auth_dashboard', 'auth_account_orders'], true)) {
        $auditShopProps($props, str_replace('auth_', '', $name), $name);
    }
    if ($name === 'auth_dashboard') {
        if (! array_key_exists('recentOrders', $props) || ! is_array($props['recentOrders'])) {
            $weakness(
                'medium',
                'dashboard',
                'Dashboard missing recentOrders prop',
                'DashboardController should share last orders for retention UX',
                $name,
            );
        } else {
            $ok('dashboard_recent_orders', 'count='.count($props['recentOrders']));
        }
    }
    $ok($name, 'HTTP '.$res->status(), $ms);
}

// ── Cart depth ───────────────────────────────────────────────────────────────
$clear = $request('DELETE', '/cart');
$assertHttp($clear, 'cart_clear');
$ok('cart_clear', 'HTTP '.$clear->status());

$addPayload = [
    'product_id' => $productId,
    'quantity' => 1,
];
if ($variantId) {
    $addPayload['variant_id'] = $variantId;
}
$add = $request('POST', '/cart', ['form' => $addPayload]);
$assertHttp($add, 'cart_add');
$ok('cart_add', 'HTTP '.$add->status());

$cartRes = $timed(static fn () => $request('GET', '/cart'));
$cart = $cartRes['response'];
$assertHttp($cart, 'cart_get');
$cartProps = $propsOf($cart);
$lines = $cartLines($cartProps);
if ($lines === []) {
    $fail('cart_lines', 'cart empty after add');
}

$lineId = (int) ($lines[0]['id'] ?? 0);
if ($lineId < 1) {
    $fail('cart_line_id', 'could not resolve cart line id');
}
$ok('cart_get', 'lines='.count($lines).' line_id='.$lineId, $cartRes['ms']);

// Browser sends application/x-www-form-urlencoded; probe for type coercion bugs.
$formPatchProbe = $request('PATCH', '/cart/'.$lineId, ['form' => ['quantity' => 2]]);
if ($formPatchProbe->status() >= 500) {
    $weakness(
        'high',
        'cart',
        'Cart PATCH with form-urlencoded quantity returns HTTP '.$formPatchProbe->status(),
        'Cast quantity to (int) in CartController::update before CartManager::update',
        'cart_patch_form_probe',
    );
}

$patch = $request('PATCH', '/cart/'.$lineId, ['json' => ['quantity' => 2]]);
$assertHttp($patch, 'cart_patch_qty');
$ok('cart_patch_qty', 'HTTP '.$patch->status());

$cartAfterPatch = $request('GET', '/cart');
$assertHttp($cartAfterPatch, 'cart_qty_verify');
$patchedLines = $cartLines($propsOf($cartAfterPatch));
$qty = (int) ($patchedLines[0]['quantity'] ?? 0);
if ($qty !== 2) {
    $fail('cart_qty_verify', "expected qty=2, got {$qty}");
}
$ok('cart_qty_verify', 'quantity='.$qty);

$coupon = $request('POST', '/cart/coupon', ['form' => ['code' => $couponCode]]);
$assertHttp($coupon, 'cart_coupon_apply', [200, 302, 303]);
$couponCart = $request('GET', '/cart');
$assertHttp($couponCart, 'cart_after_coupon');
$couponProps = $propsOf($couponCart);
$appliedCode = data_get($couponProps, 'couponCode') ?? data_get($couponProps, 'cart.coupon_code');
$discountTotal = (int) data_get($couponProps, 'cartContext.discount_total', data_get($couponProps, 'cartContext.discountTotal', 0));
if ($appliedCode !== $couponCode && $discountTotal <= 0) {
    $weakness('medium', 'promotions', "Coupon {$couponCode} did not apply", 'Verify discount is active and cart qualifies', 'cart_coupon_apply');
} else {
    $ok('cart_coupon_apply', 'code='.$couponCode.' discount='.$discountTotal);
}

$delCoupon = $request('DELETE', '/cart/coupon');
$assertHttp($delCoupon, 'cart_coupon_remove');
$ok('cart_coupon_remove', 'HTTP '.$delCoupon->status());

// Remove one line then re-add
$removeLine = $request('DELETE', '/cart/'.$lineId);
$assertHttp($removeLine, 'cart_remove_line');
$ok('cart_remove_line', 'HTTP '.$removeLine->status());

$readd = $request('POST', '/cart', ['form' => $addPayload]);
$assertHttp($readd, 'cart_readd');
$ok('cart_readd', 'HTTP '.$readd->status());

$cartFinal = $request('GET', '/cart');
$assertHttp($cartFinal, 'cart_final');
$finalLines = $cartLines($propsOf($cartFinal));
if ($finalLines === []) {
    $fail('cart_final', 'cart empty after re-add');
}
$lineId = (int) ($finalLines[0]['id'] ?? $lineId);
$ok('cart_final', 'lines='.count($finalLines));

// ── Checkout depth ───────────────────────────────────────────────────────────
$checkoutRes = $timed(static fn () => $request('GET', '/checkout'));
$checkout = $checkoutRes['response'];
$assertHttp($checkout, 'checkout_get');
$checkSlow('checkout_get', $checkoutRes['ms']);
$checkoutProps = $propsOf($checkout);
$assertInertiaComponent($checkout, 'checkout_get', 'shop/');
$auditShopProps($checkoutProps, 'checkout', 'checkout_get');
$ok('checkout_get', 'HTTP '.$checkout->status(), $checkoutRes['ms']);

$dest = $request('GET', '/checkout/destinations', [
    'query' => ['q' => $destQuery, 'limit' => 5],
]);
if (! $dest->successful()) {
    $fail('destination_search', 'HTTP '.$dest->status().' '.substr($dest->body(), 0, 400));
}
$destData = $dest->json('data') ?? [];
if (! is_array($destData) || $destData === []) {
    $fail('destination_search', 'empty results for q='.$destQuery);
}
$destination = $destData[0];
$ok('destination_search', 'id='.$destination['id'].' '.$destination['label']);

$postalCode = (string) (
    $destination['postal_code']
    ?? $destination['zip_code']
    ?? $destination['postcode']
    ?? ''
);
if ($postalCode === '' && preg_match('/\b(\d{5})\b/', (string) ($destination['label'] ?? ''), $mPostal)) {
    $postalCode = $mPostal[1];
}
if ($postalCode === '') {
    $postalCode = '12220';
}

$city = (string) ($destination['city_name'] ?? $destination['city'] ?? 'Jakarta Selatan');
$state = (string) ($destination['province_name'] ?? $destination['province'] ?? 'DKI Jakarta');

$address = $request('POST', '/checkout/shipping-address', [
    'form' => [
        'first_name' => 'Deploy',
        'last_name' => 'DeepE2E',
        'street_address' => 'Jl. Melawai Raya No. 1',
        'street_address_plus' => '',
        'postal_code' => $postalCode,
        'city' => $city,
        'state' => $state,
        'phone_number' => '081234567890',
        'rajaongkir_destination_id' => (string) $destination['id'],
        'rajaongkir_destination_label' => (string) $destination['label'],
    ],
]);
$assertHttp($address, 'shipping_address');
$ok('shipping_address', 'HTTP '.$address->status());

$checkout2Res = $timed(static fn () => $request('GET', '/checkout', ['query' => ['step' => 2]]));
$checkout2 = $checkout2Res['response'];
$assertHttp($checkout2, 'checkout_step2');
$checkSlow('checkout_step2', $checkout2Res['ms']);
$props = $propsOf($checkout2);

if (! empty($props['errors']) && is_array($props['errors'])) {
    $fail('checkout_step2', 'errors='.json_encode($props['errors']));
}

$deliveryOptions = $props['deliveryOptions'] ?? [];
$byShipment = $props['deliveryOptionsByShipment'] ?? [];
$paymentOptions = $props['paymentOptions'] ?? [];
$savedAddresses = $props['savedAddresses'] ?? null;

$flatRates = [];
if (is_array($deliveryOptions) && $deliveryOptions !== []) {
    $flatRates = $deliveryOptions;
} elseif (is_array($byShipment)) {
    foreach ($byShipment as $opts) {
        if (is_array($opts)) {
            foreach ($opts as $opt) {
                $flatRates[] = $opt;
            }
        }
    }
}
if ($flatRates === []) {
    $fail('delivery_rates', 'no rates hint='.json_encode($props['shippingRatesHint'] ?? null));
}
$rate = $flatRates[0];
$ok('delivery_rates', 'count='.count($flatRates).' first='.($rate['service_code'] ?? '?'), $checkout2Res['ms']);

if (! is_array($paymentOptions) || $paymentOptions === []) {
    $fail('payment_methods', 'none on checkout step 2');
}
$ok('payment_methods', 'count='.count($paymentOptions));

if (! is_array($savedAddresses)) {
    $weakness('medium', 'checkout', 'savedAddresses is not an array on checkout step 2', 'Check PersistUserShippingAddress mapping', 'saved_addresses');
} elseif ($savedAddresses === []) {
    $weakness('low', 'checkout', 'savedAddresses empty after shipping-address save', 'Address may not persist for user', 'saved_addresses');
} else {
    $ok('saved_addresses', 'count='.count($savedAddresses));
}

// ── Optional place-order ─────────────────────────────────────────────────────
$orderId = null;
if ($placeOrder) {
    $allocation = $props['allocation'] ?? null;

    if (is_array($allocation) && count($allocation) > 1) {
        $payload = ['rates' => []];
        foreach ($allocation as $pkg) {
            $invId = $pkg['inventory_id'];
            $opts = $byShipment[$invId] ?? $byShipment[(string) $invId] ?? [];
            $opt = is_array($opts) && $opts !== [] ? $opts[0] : $rate;
            $payload['rates'][$invId] = $opt['service_code'];
        }
        $ship = $request('POST', '/checkout/shipping-option', ['form' => $payload]);
    } else {
        $ship = $request('POST', '/checkout/shipping-option', [
            'form' => ['service_code' => $rate['service_code']],
        ]);
    }
    $assertHttp($ship, 'shipping_option');
    $ok('shipping_option', 'HTTP '.$ship->status());

    $paymentMethod = $paymentOptions[0];
    $place = $request('POST', '/checkout/place-order', [
        'form' => ['payment_method_id' => $paymentMethod['id']],
    ]);
    $assertHttp($place, 'place_order');
    $location = (string) ($place->header('Location') ?? '');
    if ($location === '' && $place->status() === 200) {
        $inertia = $place->json();
        $location = (string) data_get($inertia, 'url', data_get($inertia, 'props.redirect', ''));
    }
    $ok('place_order', 'HTTP '.$place->status().' loc='.$location);

    if (preg_match('#/(?:checkout/success|account/orders)/(\d+)#', $location, $m)) {
        $orderId = (int) $m[1];
    }
    if (! $orderId) {
        $orders = $request('GET', '/account/orders');
        $ordersProps = $propsOf($orders);
        $first = data_get($ordersProps, 'orders.data.0.id')
            ?? data_get($ordersProps, 'orders.0.id');
        $orderId = $first ? (int) $first : null;
    }
    if (! $orderId) {
        $fail('order_id', 'could not resolve created order from '.$location);
    }
    $ok('order_created', 'order_id='.$orderId);

    $success = $request('GET', '/checkout/success/'.$orderId);
    $assertHttp($success, 'checkout_success');
    $ok('checkout_success', 'HTTP '.$success->status());

    $orderShow = $request('GET', '/account/orders/'.$orderId);
    $assertHttp($orderShow, 'account_order_show');
    $ok('account_order_show', 'HTTP '.$orderShow->status());
}

$emitJson(true);
