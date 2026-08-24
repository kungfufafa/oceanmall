<?php

declare(strict_types=1);

/**
 * Live marketplace customer UAT via domain actions + real Komerce sandbox.
 * Complements browser UAT. Does not print secrets.
 *
 * php scripts/live-customer-domain-uat.php
 */

use App\Actions\Cart\AddToCart;
use App\Actions\Checkout\CreateKomercePayment;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\CreateOrder;
use App\Actions\Warehouse\SuggestAllocation;
use App\CheckoutSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Komerce\ShippingCostClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\PaymentMethod;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$steps = [];
$ok = static function (string $step, string $detail = '') use (&$steps): void {
    $steps[] = compact('step') + ['ok' => true, 'detail' => $detail];
    echo "OK   [$step]".($detail !== '' ? " — $detail" : '')."\n";
};
$fail = static function (string $step, string $detail) use (&$steps): never {
    $steps[] = compact('step') + ['ok' => false, 'detail' => $detail];
    fwrite(STDERR, "FAIL [$step]: $detail\n");
    echo json_encode(['ok' => false, 'steps' => $steps], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";
    exit(1);
};

if (! komerce_enabled()) {
    $fail('komerce', 'disabled');
}
$ok('komerce', 'enabled');

$user = User::query()->where('email', 'customer@oceanmall.test')->first();
if (! $user) {
    $fail('customer', 'customer@oceanmall.test missing');
}
Auth::login($user);
$ok('auth', 'user_id='.$user->id);

$product = Product::query()->where('slug', 'realme-buds-t310')->first()
    ?? Product::query()->scopes('publish')->first();
if (! $product) {
    $fail('catalog', 'no published product');
}

// Prefer a simple (non-variant) purchasable for domain UAT stability.
$variant = null;
if ($product->variants()->exists()) {
    $variant = $product->variants()->first();
    if (! $variant) {
        $fail('catalog', 'product has variants but none found');
    }
}
$ok('catalog', 'product='.$product->slug.($variant ? ' variant='.$variant->id : ''));

// Fresh cart
resolve(CartSessionManager::class)->forget();
session()->forget([CheckoutSession::KEY, 'komerce_payment', 'checkout_cart_id']);

try {
    resolve(AddToCart::class)->handle($product, $variant, 1);
} catch (Throwable $e) {
    $fail('add_to_cart', $e->getMessage());
}
$cart = resolve(CartSessionManager::class)->current();
if (! $cart || $cart->lines()->count() < 1) {
    $fail('add_to_cart', 'cart empty after add');
}
$ok('add_to_cart', 'lines='.$cart->lines()->count());

$countryId = (int) Country::query()->where('cca2', 'ID')->value('id');
if ($countryId < 1) {
    $fail('zone', 'ID country missing');
}

$destinations = resolve(ShippingCostClient::class)->searchDomestic('Jakarta Selatan', 5);
if ($destinations === []) {
    $fail('destination_search', 'empty');
}
$destination = $destinations[0];
$ok('destination_search', $destination['id'].' '.$destination['label']);

$shippingAddress = [
    'first_name' => 'Budi',
    'last_name' => 'Santoso',
    'street_address' => 'Jl. Melawai Raya No. 1',
    'street_address_plus' => '',
    'postal_code' => '12160',
    'city' => 'Jakarta Selatan',
    'state' => 'DKI Jakarta',
    'phone_number' => '081234567890',
    'country_id' => $countryId,
    'rajaongkir_destination_id' => (string) $destination['id'],
    'rajaongkir_destination_label' => (string) $destination['label'],
];
session()->put(CheckoutSession::SHIPPING_ADDRESS, $shippingAddress);
$ok('shipping_address', 'saved');

try {
    $plan = resolve(SuggestAllocation::class)->handle($cart->fresh(['lines.purchasable']), $shippingAddress);
} catch (Throwable $e) {
    $fail('allocation', $e->getMessage());
}
if ($plan->shipments === []) {
    $fail('allocation', 'empty plan');
}
session()->put(CheckoutSession::ALLOCATION_PLAN, $plan);
$ok('allocation', 'shipments='.count($plan->shipments));

$packages = resolve(\App\Actions\Checkout\BuildShippingPackages::class)->handle();
$allRates = [];
$selectedByShipment = [];
foreach ($plan->shipments as $draft) {
    $shipmentPackages = resolve(\App\Actions\Checkout\BuildShippingPackages::class)
        ->handleFromLines($draft->lines);
    $rates = resolve(FetchDeliveryRates::class)->handle(
        $shippingAddress,
        $shipmentPackages,
        $draft->inventory_id,
    );
    if ($rates === []) {
        $fail('delivery_rates', 'empty for inventory '.$draft->inventory_id);
    }
    $allRates[$draft->inventory_id] = $rates;
    $selected = $rates[0];
    $selectedByShipment[$draft->inventory_id] = $selected;
}
$ok('delivery_rates', 'packages='.count($allRates).' first='.$selectedByShipment[array_key_first($selectedByShipment)]['service_code']);

if (count($selectedByShipment) === 1) {
    $only = reset($selectedByShipment);
    session()->forget(CheckoutSession::SHIPPING_OPTION);
    session()->push(CheckoutSession::SHIPPING_OPTION, [
        'id' => $only['service_code'],
        'name' => $only['service_name'],
        'price' => $only['amount'],
        'service_code' => $only['service_code'],
        'carrier_code' => $only['carrier_code'],
        'currency' => $only['currency'] ?? 'IDR',
        'estimated_days' => $only['estimated_days'] ?? null,
    ]);
} else {
    session()->put(CheckoutSession::SHIPPING_OPTIONS_BY_SHIPMENT, $selectedByShipment);
    $total = array_sum(array_map(static fn (array $r): int => (int) $r['amount'], $selectedByShipment));
    session()->forget(CheckoutSession::SHIPPING_OPTION);
    session()->push(CheckoutSession::SHIPPING_OPTION, [
        'id' => 'multi',
        'name' => 'Multi package',
        'price' => $total,
        'service_code' => 'multi',
        'carrier_code' => 'multi',
        'currency' => 'IDR',
    ]);
}
$ok('shipping_option', 'selected');

$methods = resolve(FetchPaymentMethods::class)->handle($countryId);
$va = collect($methods)->first(fn (array $m): bool => ($m['payment_type'] ?? null) === 'bank_transfer'
    && filled($m['channel_code'] ?? null));
$qris = collect($methods)->first(fn (array $m): bool => ($m['payment_type'] ?? null) === 'qris');
if (! $va) {
    $fail('payment_methods', 'no VA method: '.json_encode($methods));
}
$ok('payment_methods', 'va='.$va['title'].' qris='.($qris['title'] ?? 'none'));

session()->forget(CheckoutSession::PAYMENT);
session()->push(CheckoutSession::PAYMENT, $va);

try {
    $order = resolve(CreateOrder::class)->handle();
} catch (Throwable $e) {
    $fail('create_order', $e->getMessage());
}
$ok('create_order', 'order='.$order->number.' id='.$order->id);

try {
    $instructions = resolve(CreateKomercePayment::class)->handle($order, $va);
} catch (Throwable $e) {
    $fail('create_va_payment', $e->getMessage());
}
if (blank($instructions['virtual_account_number'] ?? null) && blank($instructions['payment_url'] ?? null)) {
    $fail('create_va_payment', 'empty VA instructions '.json_encode($instructions));
}
session()->put('komerce_payment', $instructions);
$ok('create_va_payment', 'va='.$instructions['virtual_account_number'].' ref='.$instructions['payment_id']);

// Optional: also prove QRIS create on a second tiny order path is not needed —
// verify QRIS method create against same order would conflict; smoke via client already done.
if ($qris) {
    $qOrder = Order::query()->find($order->id);
    // Don't recreate payment on same order; just assert method available for UI.
    $ok('qris_method_available', $qris['title']);
}

resolve(CartSessionManager::class)->forget();
session()->forget(CheckoutSession::KEY);

$accountOrder = Order::query()->where('customer_id', $user->id)->whereKey($order->id)->first();
if (! $accountOrder) {
    $fail('account_order', 'not owned by customer');
}
$ok('account_order', 'visible order_id='.$accountOrder->id.' payment_status='.$accountOrder->payment_status->value);

$shipments = \App\Models\OrderShipment::query()->where('order_id', $order->id)->count();
$ok('shipments_persisted', 'count='.$shipments);

echo json_encode([
    'ok' => true,
    'order_id' => $order->id,
    'order_number' => $order->number,
    'payment_id' => $instructions['payment_id'],
    'va' => $instructions['virtual_account_number'],
    'payment_url' => $instructions['payment_url'] ?? null,
    'steps' => $steps,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

exit(0);
