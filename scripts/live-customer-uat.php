<?php

declare(strict_types=1);

/**
 * Live full-customer marketplace UAT against running app + real Komerce sandbox.
 * Usage: php scripts/live-customer-uat.php
 *
 * Does NOT print API keys. Exits non-zero on failure.
 */

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\PaymentMethod;
use Shopper\Core\Models\Product;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

if (! komerce_enabled()) {
    fwrite(STDERR, "FAIL: komerce_enabled() is false — set Komerce keys in .env\n");
    exit(1);
}

$base = rtrim((string) (getenv('UAT_BASE_URL') ?: 'http://127.0.0.1:8000'), '/');
$cookieJar = new \GuzzleHttp\Cookie\CookieJar;
$client = Http::baseUrl($base)
    ->withOptions(['cookies' => $cookieJar, 'allow_redirects' => false])
    ->timeout(60)
    ->acceptJson();

$steps = [];
$fail = static function (string $step, string $detail) use (&$steps): never {
    $steps[] = ['step' => $step, 'ok' => false, 'detail' => $detail];
    fwrite(STDERR, "FAIL [$step]: $detail\n");
    echo json_encode(['ok' => false, 'steps' => $steps], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(1);
};
$ok = static function (string $step, string $detail = '') use (&$steps): void {
    $steps[] = ['step' => $step, 'ok' => true, 'detail' => $detail];
    fwrite(STDOUT, "OK   [$step]".($detail !== '' ? " — $detail" : '')."\n");
};

// --- CSRF + session bootstrap ---
$home = $client->get('/');
if (! $home->successful()) {
    $fail('home', 'HTTP '.$home->status());
}
$ok('home', 'HTTP '.$home->status());

$loginPage = $client->get('/login');
if (! $loginPage->successful()) {
    $fail('login_page', 'HTTP '.$loginPage->status());
}
$xsrf = null;
foreach ($cookieJar->toArray() as $cookie) {
    if (($cookie['Name'] ?? '') === 'XSRF-TOKEN') {
        $xsrf = urldecode((string) $cookie['Value']);
    }
}
if (! is_string($xsrf) || $xsrf === '') {
    $fail('csrf', 'missing XSRF-TOKEN cookie');
}
$ok('csrf', 'token present');

$inertiaVersion = file_exists(public_path('build/manifest.json'))
    ? hash_file('xxh128', public_path('build/manifest.json'))
    : '';

$withCsrf = static function () use ($client, &$xsrf, $inertiaVersion) {
    return $client->withHeaders([
        'X-XSRF-TOKEN' => $xsrf,
        'X-Requested-With' => 'XMLHttpRequest',
        'X-Inertia' => 'true',
        'X-Inertia-Version' => $inertiaVersion,
        'Accept' => 'text/html, application/xhtml+xml',
        'Referer' => 'http://127.0.0.1:8000/login',
    ]);
};

// Refresh XSRF from jar after each mutating response
$refreshXsrf = static function () use ($cookieJar, &$xsrf): void {
    foreach ($cookieJar->toArray() as $cookie) {
        if (($cookie['Name'] ?? '') === 'XSRF-TOKEN') {
            $xsrf = urldecode((string) $cookie['Value']);
        }
    }
};

// --- Login ---
$login = $withCsrf()->asForm()->post('/login', [
    'email' => 'customer@oceanmall.test',
    'password' => 'password123',
]);
$refreshXsrf();
if (! in_array($login->status(), [200, 302, 303, 409], true)) {
    $fail('login', 'HTTP '.$login->status().' body='.substr($login->body(), 0, 300));
}
$ok('login', 'HTTP '.$login->status());

// --- Browse surfaces ---
foreach ([
    'shop_index' => '/shop',
    'categories' => '/categories',
    'search' => '/search?q=realme',
    'product' => '/shop/realme-13-pro-5g',
    'cart_get' => '/cart',
] as $name => $path) {
    $res = $withCsrf()->get($path);
    $refreshXsrf();
    if (! $res->successful() && $res->status() !== 409) {
        $fail($name, 'HTTP '.$res->status());
    }
    $ok($name, 'HTTP '.$res->status());
}

// --- Add to cart ---
$product = Product::query()->where('slug', 'realme-13-pro-5g')->first();
if (! $product) {
    $fail('product_model', 'realme-13-pro-5g missing');
}
$add = $withCsrf()->asForm()->post('/cart', [
    'product_id' => $product->id,
    'quantity' => 1,
]);
$refreshXsrf();
if (! in_array($add->status(), [200, 302, 303, 409], true)) {
    $fail('cart_add', 'HTTP '.$add->status().' '.substr($add->body(), 0, 400));
}
$ok('cart_add', 'HTTP '.$add->status());

$cartPage = $withCsrf()->get('/cart');
$refreshXsrf();
if (! $cartPage->successful() && $cartPage->status() !== 409) {
    $fail('cart_after_add', 'HTTP '.$cartPage->status());
}
$ok('cart_after_add', 'HTTP '.$cartPage->status());

// --- Checkout page ---
$checkout = $withCsrf()->get('/checkout');
$refreshXsrf();
if (! in_array($checkout->status(), [200, 302, 303, 409], true)) {
    $fail('checkout_get', 'HTTP '.$checkout->status().' '.substr($checkout->body(), 0, 400));
}
$ok('checkout_get', 'HTTP '.$checkout->status());

// --- Destination search (live Komerce) ---
$dest = $withCsrf()->get('/checkout/destinations', ['q' => 'Jakarta Selatan', 'limit' => 5]);
$refreshXsrf();
if (! $dest->successful()) {
    $fail('destination_search', 'HTTP '.$dest->status().' '.substr($dest->body(), 0, 400));
}
$destData = $dest->json('data') ?? [];
if (! is_array($destData) || $destData === []) {
    $fail('destination_search', 'empty results');
}
$destination = $destData[0];
$ok('destination_search', 'id='.$destination['id'].' '.$destination['label']);

$countryId = Country::query()->where('cca2', 'ID')->value('id');
if (! $countryId) {
    $fail('country', 'Indonesia missing');
}

// --- Save shipping address ---
$address = $withCsrf()->asForm()->post('/checkout/shipping-address', [
    'first_name' => 'Budi',
    'last_name' => 'Santoso',
    'street_address' => 'Jl. Melawai Raya No. 1',
    'street_address_plus' => '',
    'postal_code' => '12160',
    'city' => 'Jakarta Selatan',
    'state' => 'DKI Jakarta',
    'phone_number' => '081234567890',
    'rajaongkir_destination_id' => (string) $destination['id'],
    'rajaongkir_destination_label' => (string) $destination['label'],
]);
$refreshXsrf();
if (! in_array($address->status(), [200, 302, 303, 409], true)) {
    $fail('shipping_address', 'HTTP '.$address->status().' '.substr($address->body(), 0, 500));
}
$ok('shipping_address', 'HTTP '.$address->status());

// --- Reload checkout for rates ---
$checkout2 = $withCsrf()->withHeaders(['X-Inertia' => 'true'])->get('/checkout?step=2');
$refreshXsrf();
$body = $checkout2->json();
$props = is_array($body) ? ($body['props'] ?? []) : [];
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
    $fail('delivery_rates', 'no rates. hint='.json_encode($props['shippingRatesHint'] ?? null).' allocation='.json_encode($allocation));
}
$rate = $flatRates[0];
$ok('delivery_rates', 'count='.count($flatRates).' first='.($rate['service_code'] ?? '?'));

if (! is_array($paymentOptions) || $paymentOptions === []) {
    $fail('payment_methods', 'none exposed on checkout');
}
$vaMethod = collect($paymentOptions)->first(fn ($m) => ($m['payment_type'] ?? null) === 'bank_transfer' || ($m['channel_code'] ?? null) !== null)
    ?? $paymentOptions[0];
$ok('payment_methods', 'count='.count($paymentOptions).' selected='.($vaMethod['title'] ?? $vaMethod['id']));

// --- Save shipping option ---
if (is_array($allocation) && count($allocation) > 1) {
    $payload = ['rates' => []];
    foreach ($allocation as $pkg) {
        $invId = $pkg['inventory_id'];
        $opts = $byShipment[$invId] ?? $byShipment[(string) $invId] ?? [];
        $opt = is_array($opts) && $opts !== [] ? $opts[0] : $rate;
        $payload['rates'][$invId] = $opt['service_code'];
    }
    $ship = $withCsrf()->asForm()->post('/checkout/shipping-option', $payload);
} else {
    $ship = $withCsrf()->asForm()->post('/checkout/shipping-option', [
        'service_code' => $rate['service_code'],
    ]);
}
$refreshXsrf();
if (! in_array($ship->status(), [200, 302, 303, 409], true)) {
    $fail('shipping_option', 'HTTP '.$ship->status().' '.substr($ship->body(), 0, 500));
}
$ok('shipping_option', 'HTTP '.$ship->status());

// --- Place order (live Komerce VA) ---
$place = $withCsrf()->asForm()->post('/checkout/place-order', [
    'payment_method_id' => $vaMethod['id'],
]);
$refreshXsrf();
$location = $place->header('Location') ?? '';
if (! in_array($place->status(), [200, 302, 303, 409], true)) {
    $fail('place_order', 'HTTP '.$place->status().' '.substr($place->body(), 0, 800));
}
if ($location === '' && $place->status() !== 409) {
    // Inertia may return 200 with redirect props
    $inertia = $place->json();
    $location = (string) data_get($inertia, 'url', data_get($inertia, 'props.redirect', ''));
}
$ok('place_order', 'HTTP '.$place->status().' loc='.$location);

// Follow redirect to success or account
$orderId = null;
if (preg_match('#/(?:checkout/success|account/orders)/(\d+)#', $location, $m)) {
    $orderId = (int) $m[1];
}
if (! $orderId) {
    // Try latest order for customer
    $user = User::query()->where('email', 'customer@oceanmall.test')->first();
    $orderId = $user
        ? \Shopper\Core\Models\Order::query()->where('customer_id', $user->id)->latest('id')->value('id')
        : null;
}
if (! $orderId) {
    $fail('order_id', 'could not resolve created order from redirect '.$location);
}
$ok('order_created', 'order_id='.$orderId);

$success = $withCsrf()->get('/checkout/success/'.$orderId);
$refreshXsrf();
if (! in_array($success->status(), [200, 302, 303, 409], true)) {
    // may redirect to account if session cleared
    $account = $withCsrf()->get('/account/orders/'.$orderId);
    $refreshXsrf();
    if (! $account->successful() && $account->status() !== 409) {
        $fail('success_or_account', 'success='.$success->status().' account='.$account->status());
    }
    $ok('account_order', 'HTTP '.$account->status());
    $props = $account->json('props') ?? [];
} else {
    $ok('checkout_success', 'HTTP '.$success->status());
    $props = $success->json('props') ?? [];
}

$komercePayment = $props['komercePayment'] ?? null;
if (! is_array($komercePayment)) {
    // fetch account order show
    $account = $withCsrf()->get('/account/orders/'.$orderId);
    $refreshXsrf();
    $props = $account->json('props') ?? [];
    $komercePayment = $props['komercePayment'] ?? null;
}
if (! is_array($komercePayment) || blank($komercePayment['payment_id'] ?? null)) {
    $fail('payment_instructions', 'missing komercePayment props');
}
if (blank($komercePayment['virtual_account_number'] ?? null) && blank($komercePayment['payment_url'] ?? null)) {
    $fail('payment_instructions', 'VA/payment_url empty: '.json_encode($komercePayment));
}
$ok('payment_instructions', 'va='.($komercePayment['virtual_account_number'] ?? 'n/a').' id='.$komercePayment['payment_id']);

// --- Account surfaces ---
foreach ([
    'account_orders' => '/account/orders',
    'account_order_show' => '/account/orders/'.$orderId,
    'account_addresses' => '/account/addresses',
] as $name => $path) {
    $res = $withCsrf()->get($path);
    $refreshXsrf();
    if (! $res->successful() && $res->status() !== 409) {
        $fail($name, 'HTTP '.$res->status());
    }
    $ok($name, 'HTTP '.$res->status());
}

echo json_encode([
    'ok' => true,
    'order_id' => $orderId,
    'payment_id' => $komercePayment['payment_id'] ?? null,
    'va' => $komercePayment['virtual_account_number'] ?? null,
    'steps' => $steps,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

exit(0);
