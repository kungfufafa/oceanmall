<?php

declare(strict_types=1);

/**
 * Deploy-targeted HTTP E2E for OceanMall storefront (no local DB dependency).
 *
 * Runs against a live URL after deploy. Discovers products from Inertia props.
 *
 * Modes:
 *   smoke     — public pages, login, shop, cart, /cpanel/login (default)
 *   customer  — + checkout → place unpaid order → payment instructions
 *   full      — + signed payment webhook mark-paid (requires secret + confirm)
 *
 * Usage:
 *   DEPLOY_BASE_URL=https://staging.example.com \
 *   DEPLOY_E2E_EMAIL=customer@oceanmall.test \
 *   DEPLOY_E2E_PASSWORD=password123 \
 *   DEPLOY_E2E_MODE=smoke \
 *   php scripts/deploy-e2e.php
 *
 * For customer/full against non-local hosts also set:
 *   DEPLOY_E2E_CONFIRM=YES
 *
 * For full:
 *   DEPLOY_E2E_WEBHOOK_SECRET=...   # same as KOMERCE_WEBHOOK_SECRET on server
 *   DEPLOY_E2E_PAYMENT=qris|va      # preferred channel when available (default: any)
 *
 * Does NOT print secrets. Exit 0 on pass, 1 on fail.
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;
use App\Support\KomerceCallbackSignature;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$base = rtrim((string) (getenv('DEPLOY_BASE_URL') ?: getenv('UAT_BASE_URL') ?: ''), '/');
$email = (string) (getenv('DEPLOY_E2E_EMAIL') ?: 'customer@oceanmall.test');
$password = (string) (getenv('DEPLOY_E2E_PASSWORD') ?: 'password123');
$mode = strtolower((string) (getenv('DEPLOY_E2E_MODE') ?: 'smoke'));
$confirm = strtoupper((string) (getenv('DEPLOY_E2E_CONFIRM') ?: ''));
$webhookSecret = (string) (getenv('DEPLOY_E2E_WEBHOOK_SECRET') ?: '');
$preferPayment = strtolower((string) (getenv('DEPLOY_E2E_PAYMENT') ?: 'any'));
$productSlugHint = trim((string) (getenv('DEPLOY_E2E_PRODUCT_SLUG') ?: ''));
$destQuery = trim((string) (getenv('DEPLOY_E2E_DESTINATION') ?: 'Jakarta Selatan'));

$allowedModes = ['smoke', 'customer', 'full'];
if (! in_array($mode, $allowedModes, true)) {
    fwrite(STDERR, "FAIL: DEPLOY_E2E_MODE must be smoke|customer|full (got {$mode})\n");
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

if (! $isLocal && in_array($mode, ['customer', 'full'], true) && $confirm !== 'YES') {
    fwrite(STDERR, "FAIL: non-local {$mode} requires DEPLOY_E2E_CONFIRM=YES (creates real unpaid/paid side-effects)\n");
    exit(1);
}

if ($mode === 'full' && $webhookSecret === '') {
    fwrite(STDERR, "FAIL: full mode requires DEPLOY_E2E_WEBHOOK_SECRET\n");
    exit(1);
}

$steps = [];
$ok = static function (string $step, string $detail = '') use (&$steps): void {
    $steps[] = ['step' => $step, 'ok' => true, 'detail' => $detail];
    echo "OK   [$step]".($detail !== '' ? " — $detail" : '')."\n";
};
$fail = static function (string $step, string $detail) use (&$steps, $base, $mode): never {
    $steps[] = ['step' => $step, 'ok' => false, 'detail' => $detail];
    fwrite(STDERR, "FAIL [$step]: $detail\n");
    echo json_encode([
        'ok' => false,
        'base' => $base,
        'mode' => $mode,
        'steps' => $steps,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(1);
};
$warn = static function (string $step, string $detail) use (&$steps): void {
    $steps[] = ['step' => $step, 'ok' => true, 'warn' => true, 'detail' => $detail];
    echo "WARN [$step] — $detail\n";
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
            'PATCH' => $pending->asForm()->patch($path, $options['form'] ?? []),
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

echo "DEPLOY E2E base={$base} mode={$mode} host={$host}\n";

// ── Public smoke ─────────────────────────────────────────────────────────────
$home = $makeClient()->get('/');
$refreshXsrf();
if (! $home->successful()) {
    $fail('home', 'HTTP '.$home->status());
}
$ok('home', 'HTTP '.$home->status());

// Seed session + XSRF via login page HTML (non-Inertia)
$loginPage = $htmlRequest('GET', '/login');
$assertHttp($loginPage, 'login_page', [200]);
if ($xsrf === '') {
    $fail('csrf', 'missing XSRF-TOKEN cookie');
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

foreach ([
    'shop_index' => '/shop',
    'categories' => '/categories',
    'search' => '/search?q=realme',
    'cart_empty' => '/cart',
] as $name => $path) {
    $res = $request('GET', $path);
    $assertHttp($res, $name);
    $ok($name, 'HTTP '.$res->status());
}

$cpanel = $makeClient()->get('/cpanel/login');
$refreshXsrf();
if (! in_array($cpanel->status(), [200, 302], true)) {
    $fail('cpanel_login', 'HTTP '.$cpanel->status());
}
$ok('cpanel_login', 'HTTP '.$cpanel->status());

// Refresh CSRF immediately before login (avoid stale token after Inertia hops)
$htmlRequest('GET', '/login');
if ($xsrf === '') {
    $fail('csrf_refresh', 'XSRF missing before login');
}

$login = $htmlRequest('POST', '/login', [
    'form' => [
        'email' => $email,
        'password' => $password,
    ],
    'referer' => $base.'/login',
]);
$assertHttp($login, 'login');
$ok('login', 'HTTP '.$login->status().' as '.$email);

// Ensure Indonesia shipping zone (required for RajaOngkir destination + rates)
$zone = $htmlRequest('PATCH', '/zone', [
    'form' => ['country_code' => 'ID'],
    'referer' => $base.'/',
]);
$assertHttp($zone, 'zone');
$ok('zone', 'HTTP '.$zone->status().' country=ID');

$dashboard = $request('GET', '/dashboard');
$assertHttp($dashboard, 'dashboard');
$ok('dashboard', 'HTTP '.$dashboard->status());

if ($mode === 'smoke') {
    echo json_encode([
        'ok' => true,
        'base' => $base,
        'mode' => $mode,
        'steps' => $steps,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
}

// ── Discover product from shop Inertia props ─────────────────────────────────
$shop = $request('GET', '/shop');
$assertHttp($shop, 'shop_props', [200, 409]);
$shopProps = $propsOf($shop);
$products = data_get($shopProps, 'products.data');
if (! is_array($products) || $products === []) {
    // paginator may be under products directly
    $products = data_get($shopProps, 'products') ?? [];
    if (isset($products['data']) && is_array($products['data'])) {
        $products = $products['data'];
    }
}
if (! is_array($products) || $products === []) {
    $fail('catalog', 'no products on /shop');
}

$chosen = null;
if ($productSlugHint !== '') {
    foreach ($products as $p) {
        if (($p['slug'] ?? null) === $productSlugHint) {
            $chosen = $p;
            break;
        }
    }
}
if ($chosen === null) {
    // Prefer a product without required variants if detectable; else first.
    foreach ($products as $p) {
        if (! empty($p['id'])) {
            $chosen = $p;
            break;
        }
    }
}
if ($chosen === null || empty($chosen['id']) || empty($chosen['slug'])) {
    $fail('catalog', 'could not pick product');
}
$ok('catalog', 'product='.$chosen['slug'].' id='.$chosen['id']);

$pdp = $request('GET', '/shop/'.$chosen['slug']);
$assertHttp($pdp, 'pdp');
$pdpProps = $propsOf($pdp);
$productId = (int) data_get($pdpProps, 'product.id', $chosen['id']);
$variantId = null;
$variantOptions = data_get($pdpProps, 'variantOptions');
if (is_array($variantOptions) && ($variantOptions['hasStructuredAttributes'] ?? false)) {
    $index = $variantOptions['variantIndex'] ?? [];
    if (is_array($index) && $index !== []) {
        $variantId = (int) reset($index);
    }
}
$ok('pdp', 'product_id='.$productId.($variantId ? " variant_id={$variantId}" : ''));

// Clear cart best-effort
$request('DELETE', '/cart');

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

$cart = $request('GET', '/cart');
$assertHttp($cart, 'cart_after_add');
$cartProps = $propsOf($cart);
$lineCount = (int) data_get($cartProps, 'cart.lines_count', count(data_get($cartProps, 'cart.lines', []) ?: []));
if ($lineCount < 1 && data_get($cartProps, 'cart') === null) {
    // lines may be nested relation array
    $lines = data_get($cartProps, 'cart.lines');
    if (! is_array($lines) || count($lines) < 1) {
        $fail('cart_after_add', 'cart still empty after add');
    }
}
$ok('cart_after_add', 'HTTP '.$cart->status());

$checkout = $request('GET', '/checkout');
$assertHttp($checkout, 'checkout_get');
$ok('checkout_get', 'HTTP '.$checkout->status());

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

// Prefer postal code from destination payload when present.
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
        'last_name' => 'E2E',
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

$checkout2 = $request('GET', '/checkout', [
    'query' => ['step' => 2],
]);
$assertHttp($checkout2, 'checkout_rates');
$props = $propsOf($checkout2);
if (! empty($props['errors']) && is_array($props['errors'])) {
    $fail('checkout_rates', 'errors='.json_encode($props['errors']));
}
$deliveryOptions = $props['deliveryOptions'] ?? [];
$byShipment = $props['deliveryOptionsByShipment'] ?? [];
$allocation = $props['allocation'] ?? null;
$paymentOptions = $props['paymentOptions'] ?? [];

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
$ok('delivery_rates', 'count='.count($flatRates).' first='.($rate['service_code'] ?? '?'));

if (! is_array($paymentOptions) || $paymentOptions === []) {
    $fail('payment_methods', 'none on checkout');
}

$pickPayment = static function (array $options, string $prefer): array {
    if ($prefer === 'qris') {
        foreach ($options as $m) {
            if (($m['payment_type'] ?? '') === 'qris' || str_contains(strtolower((string) ($m['title'] ?? '')), 'qris')) {
                return $m;
            }
        }
    }
    if ($prefer === 'va') {
        foreach ($options as $m) {
            if (($m['payment_type'] ?? '') === 'bank_transfer' || ($m['channel_code'] ?? null)) {
                return $m;
            }
        }
    }

    return $options[0];
};
$paymentMethod = $pickPayment($paymentOptions, $preferPayment);
$ok('payment_methods', 'count='.count($paymentOptions).' selected='.($paymentMethod['title'] ?? $paymentMethod['id']));

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

$orderId = null;
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
$successProps = $propsOf($success);
$komercePayment = $successProps['komercePayment'] ?? null;
if (! is_array($komercePayment)) {
    $account = $request('GET', '/account/orders/'.$orderId);
    $assertHttp($account, 'account_order_show');
    $komercePayment = $propsOf($account)['komercePayment'] ?? null;
}
if (! is_array($komercePayment) || blank($komercePayment['payment_id'] ?? null)) {
    $fail('payment_instructions', 'missing komercePayment');
}
$hasVa = filled($komercePayment['virtual_account_number'] ?? null);
$hasQris = filled($komercePayment['qris_string'] ?? null) || filled($komercePayment['payment_url'] ?? null);
if (! $hasVa && ! $hasQris) {
    $fail('payment_instructions', 'VA/QRIS empty');
}
$ok('payment_instructions', 'type='.($komercePayment['payment_type'] ?? '?').' payment_id='.$komercePayment['payment_id']);

foreach ([
    'account_orders' => '/account/orders',
    'account_order_show' => '/account/orders/'.$orderId,
    'account_addresses' => '/account/addresses',
] as $name => $path) {
    $res = $request('GET', $path);
    $assertHttp($res, $name);
    $ok($name, 'HTTP '.$res->status());
}

if ($mode === 'customer') {
    echo json_encode([
        'ok' => true,
        'base' => $base,
        'mode' => $mode,
        'order_id' => $orderId,
        'payment_id' => $komercePayment['payment_id'] ?? null,
        'payment_type' => $komercePayment['payment_type'] ?? null,
        'steps' => $steps,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(0);
}

// ── full: simulate paid webhook (HMAC-signed, same as Komerce docs) ──────────
$paymentId = (string) $komercePayment['payment_id'];
$amount = (int) ($komercePayment['amount'] ?? 0);
$paymentType = (string) ($komercePayment['payment_type'] ?? '');
// QRISLY history ids are numeric-ish / non-KPAY; Payment API QRIS uses KPAY-… and the payment webhook.
$useQrislyWebhook = $paymentType === 'qris'
    && ! str_starts_with($paymentId, 'KPAY-')
    && (getenv('DEPLOY_E2E_FORCE_QRISLY_WEBHOOK') === '1' || ! str_contains($paymentId, '/KM/'));

if ($useQrislyWebhook) {
    $webhookPath = '/webhooks/komerce/qrisly';
    $payload = [
        'event' => 'payment.success',
        'data' => [
            'qris_history_id' => $paymentId,
            'payment_status' => 'paid',
            'amount' => $amount,
        ],
    ];
} else {
    $webhookPath = '/webhooks/komerce/payment';
    $payload = [
        'payment_id' => $paymentId,
        'order_id' => (string) $orderId,
        'status' => 'PAID',
        'amount' => $amount,
    ];
}

$rawBody = json_encode($payload, JSON_THROW_ON_ERROR);
$signature = KomerceCallbackSignature::sign($rawBody, $webhookSecret);

$webhook = $makeClient()->withHeaders([
    'X-Callback-Api-Key' => $signature,
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
])->withBody($rawBody, 'application/json')->post($webhookPath);

if (! $webhook->successful()) {
    $status = $webhook->status();
    $body = substr($webhook->body(), 0, 400);
    // Sandbox unpaid payments correctly return 409 not_paid — treat as soft result unless forced.
    if ($status === 409 && str_contains($webhook->body(), 'not_paid')) {
        $warn(
            'webhook_paid',
            'Komerce still reports unpaid (expected on sandbox without real settle). '.$body,
        );
    } else {
        $fail('webhook_paid', 'HTTP '.$status.' '.$body.' path='.$webhookPath);
    }
} else {
    $ok('webhook_paid', 'HTTP '.$webhook->status().' path='.$webhookPath);
}

$sync = $request('POST', '/account/orders/'.$orderId.'/sync-payment', [
    'form' => ['silent' => 1],
]);
if (in_array($sync->status(), [200, 302, 303], true)) {
    $ok('sync_payment', 'HTTP '.$sync->status());
} else {
    $warn('sync_payment', 'HTTP '.$sync->status());
}

$paidShow = $request('GET', '/account/orders/'.$orderId);
$assertHttp($paidShow, 'order_after_paid');
$paidProps = $propsOf($paidShow);
$paymentStatusRaw = data_get($paidProps, 'order.payment_status');
$paymentStatus = is_array($paymentStatusRaw)
    ? strtolower((string) ($paymentStatusRaw['value'] ?? $paymentStatusRaw['label'] ?? ''))
    : strtolower((string) (
        $paymentStatusRaw
        ?? data_get($paidProps, 'order.paymentStatus')
        ?? ''
    ));

$isPaid = $paymentStatus !== '' && (
    str_contains($paymentStatus, 'paid')
    || $paymentStatus === 'authorized'
);
$requirePaid = strtoupper((string) (getenv('DEPLOY_E2E_REQUIRE_PAID') ?: '')) === 'YES';

if ($isPaid) {
    $ok('payment_status', $paymentStatus);
} elseif ($requirePaid) {
    $fail('payment_status', 'expected paid, got '.($paymentStatus !== '' ? $paymentStatus : 'unknown'));
} else {
    $warn(
        'payment_status',
        'order still unpaid remotely — checkout+instructions OK; settle in Komerce or set DEPLOY_E2E_REQUIRE_PAID=YES after real pay',
    );
}

echo json_encode([
    'ok' => true,
    'base' => $base,
    'mode' => $mode,
    'order_id' => $orderId,
    'payment_id' => $paymentId,
    'payment_type' => $komercePayment['payment_type'] ?? null,
    'paid' => $isPaid,
    'steps' => $steps,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

exit(0);
