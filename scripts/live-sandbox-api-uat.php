<?php

declare(strict_types=1);

/**
 * Live Collaborator/RajaOngkir sandbox UAT (no HTTP fakes, no simulated paid).
 *
 * php scripts/live-sandbox-api-uat.php
 */

use App\Actions\Cart\AddToCart;
use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\CreateKomercePayment;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\CreateOrder;
use App\Actions\Warehouse\SuggestAllocation;
use App\CheckoutSession;
use App\Models\Product;
use App\Models\User;
use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\ShippingCostClient;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Auth;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Models\Country;
use Shopper\Core\Models\Inventory;

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

if (! komerce_payment_enabled() || ! komerce_shipping_cost_enabled() || ! komerce_shipping_delivery_enabled()) {
    $fail('keys', 'payment='.(komerce_payment_enabled() ? 'yes' : 'no').' cost='.(komerce_shipping_cost_enabled() ? 'yes' : 'no').' delivery='.(komerce_shipping_delivery_enabled() ? 'yes' : 'no'));
}
$ok('keys', 'payment/cost/delivery set; qrisly='.(qrisly_enabled() ? 'yes' : 'no').' webhook_secret='.(trim((string) config('komerce.webhook_secret')) !== '' ? 'yes' : 'no'));

$inventory = Inventory::query()->where('is_default', true)->first();
$originId = trim((string) $inventory?->getAttribute('rajaongkir_origin_id'));
if ($originId === '') {
    $fail('origin', 'default inventory missing rajaongkir_origin_id');
}

$originHit = null;
foreach (['Kertawinangun', 'Kedawung', '45153'] as $originQuery) {
    try {
        $originHits = resolve(ShippingCostClient::class)->searchDomestic($originQuery, 20);
    } catch (Throwable $e) {
        $fail('cost_search_origin', $e->getMessage());
    }
    $originHit = collect($originHits)->first(static fn (array $row): bool => (string) $row['id'] === $originId);
    if (is_array($originHit)) {
        break;
    }
}
if (! is_array($originHit)) {
    $fail('origin', 'id '.$originId.' not found in Cost search for Kertawinangun/Kedawung/45153');
}
$ok('origin', $originId.' '.$originHit['label']);

try {
    $destinations = resolve(ShippingCostClient::class)->searchDomestic('Jakarta Selatan', 5);
} catch (Throwable $e) {
    $fail('cost_search_destination', $e->getMessage());
}
if ($destinations === []) {
    $fail('cost_search_destination', 'empty');
}
$destination = $destinations[0];
$ok('cost_search_destination', $destination['id'].' '.$destination['label']);

try {
    $quote = resolve(ShippingCostClient::class)->calculate(
        ['id' => $originId],
        ['id' => $destination['id']],
        1000,
        ['jne', 'jnt', 'sicepat'],
    );
} catch (Throwable $e) {
    $fail('cost_calculate', $e->getMessage());
}
$quoteRows = data_get($quote, 'data', []);
$quoteCount = is_array($quoteRows) ? count($quoteRows) : 0;
if ($quoteCount < 1) {
    $fail('cost_calculate', 'empty rates meta='.json_encode(data_get($quote, 'meta')));
}
$firstQuote = is_array($quoteRows[0] ?? null) ? $quoteRows[0] : [];
$ok('cost_calculate', $quoteCount.' services e.g. '.($firstQuote['code'] ?? $firstQuote['name'] ?? 'n/a').' cost='.($firstQuote['cost'] ?? $firstQuote['value'] ?? 'n/a'));

try {
    $deliveryHits = resolve(ShippingDeliveryClient::class)->searchDestinations('Cirebon');
} catch (Throwable $e) {
    $fail('delivery_search', $e->getMessage());
}
$deliveryRows = data_get($deliveryHits, 'data', $deliveryHits);
$deliveryCount = is_array($deliveryRows) ? count($deliveryRows) : 0;
if ($deliveryCount < 1) {
    $fail('delivery_search', 'empty');
}
$ok('delivery_search', $deliveryCount.' hits');

try {
    $catalog = resolve(PaymentClient::class)->listMethods();
} catch (Throwable $e) {
    $fail('payment_catalog', $e->getMessage());
}
$catalogRows = data_get($catalog, 'data', []);
$catalogCount = is_array($catalogRows) ? count($catalogRows) : 0;
if ($catalogCount < 1) {
    $fail('payment_catalog', 'empty');
}
$types = collect(is_array($catalogRows) ? $catalogRows : [])
    ->map(static fn (mixed $row): string => is_array($row) ? strtolower((string) ($row['payment_type'] ?? '')) : '')
    ->filter()
    ->unique()
    ->values()
    ->all();
$ok('payment_catalog', $catalogCount.' methods types='.implode(',', $types));

$customer = User::query()->where('email', 'customer@oceanmall.test')->first();
if (! $customer) {
    $fail('customer', 'customer@oceanmall.test missing');
}
Auth::login($customer);

$product = Product::query()->where('slug', 'realme-buds-t310')->first()
    ?? Product::query()->scopes('publish')->first();
if (! $product) {
    $fail('catalog', 'no published product');
}
$variant = $product->variants()->exists() ? $product->variants()->first() : null;

resolve(CartSessionManager::class)->forget();
session()->forget([CheckoutSession::KEY, 'komerce_payment', 'checkout_cart_id']);

try {
    resolve(AddToCart::class)->handle($product, $variant, 1);
} catch (Throwable $e) {
    $fail('add_to_cart', $e->getMessage());
}
$cart = resolve(CartSessionManager::class)->current();
if (! $cart || $cart->lines()->count() < 1) {
    $fail('add_to_cart', 'cart empty');
}
$ok('add_to_cart', 'product='.$product->slug);

$countryId = (int) Country::query()->where('cca2', 'ID')->value('id');
$shippingAddress = [
    'first_name' => 'Budi',
    'last_name' => 'Santoso',
    'street_address' => 'Jl. Melawai Raya No. 1',
    'street_address_plus' => '',
    'postal_code' => (string) ($destination['zip_code'] ?? '12160'),
    'city' => 'Jakarta Selatan',
    'state' => 'DKI Jakarta',
    'phone_number' => '081234567890',
    'country_id' => $countryId,
    'rajaongkir_destination_id' => (string) $destination['id'],
    'rajaongkir_destination_label' => (string) $destination['label'],
];
session()->put(CheckoutSession::SHIPPING_ADDRESS, $shippingAddress);

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

$selectedByShipment = [];
foreach ($plan->shipments as $draft) {
    $shipmentPackages = resolve(BuildShippingPackages::class)->handleFromLines($draft->lines);
    $rates = resolve(FetchDeliveryRates::class)->handle(
        $shippingAddress,
        $shipmentPackages,
        $draft->inventory_id,
    );
    if ($rates === []) {
        $fail('checkout_rates', 'empty for inventory '.$draft->inventory_id);
    }
    $selectedByShipment[$draft->inventory_id] = $rates[0];
}
$sampleRate = reset($selectedByShipment);
$ok('checkout_rates', ($sampleRate['carrier_code'] ?? '?').' '.($sampleRate['service_name'] ?? '?').' amount='.($sampleRate['amount'] ?? '?'));

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

$methods = resolve(FetchPaymentMethods::class)->handle($countryId);
$qris = collect($methods)->first(static fn (array $m): bool => ($m['payment_type'] ?? null) === 'qris');
$va = collect($methods)->first(static fn (array $m): bool => ($m['payment_type'] ?? null) === 'bank_transfer');
if (! $qris && ! $va) {
    $fail('checkout_methods', 'no QRIS or VA: '.json_encode(array_column($methods, 'title')));
}
$selected = $qris ?? $va;
$ok('checkout_methods', 'using='.($selected['payment_type'] ?? '?').' title='.($selected['title'] ?? '?'));
session()->forget(CheckoutSession::PAYMENT);
session()->push(CheckoutSession::PAYMENT, $selected);

try {
    $order = resolve(CreateOrder::class)->handle();
} catch (Throwable $e) {
    $fail('create_order', $e->getMessage());
}
$ok('create_order', 'order='.$order->number.' total='.$order->price_amount);

try {
    $instructions = resolve(CreateKomercePayment::class)->handle($order, $selected);
} catch (Throwable $e) {
    $fail('create_payment', $e->getMessage());
}
$paymentId = (string) ($instructions['payment_id'] ?? '');
if ($paymentId === '') {
    $fail('create_payment', 'empty payment_id');
}
$ok(
    'create_payment',
    'ref='.$paymentId
    .' type='.($instructions['payment_type'] ?? '?')
    .' bank='.($instructions['bank_code'] ?? '-')
    .' va='.(filled($instructions['virtual_account_number'] ?? null) ? 'yes' : 'no')
    .' qris='.(filled($instructions['qris_string'] ?? null) ? 'yes' : 'no')
    .' url='.(filled($instructions['payment_url'] ?? null) ? 'yes' : 'no')
    .' amount='.($instructions['amount'] ?? $order->price_amount),
);

try {
    $status = resolve(PaymentClient::class)->getStatus($paymentId);
} catch (Throwable $e) {
    $fail('payment_status', $e->getMessage());
}
$remoteStatus = strtoupper((string) data_get($status, 'data.status', data_get($status, 'status', 'unknown')));
$ok('payment_status', $remoteStatus);

if ($remoteStatus === 'PAID') {
    $ok('paid', 'Collaborator reports PAID. AWB still needs queue/terminating fulfillment.');
} else {
    echo "STOP [pay_to_awb] — payment live but {$remoteStatus}. Bayar di portal/VA/QRIS, lalu php artisan komerce:uat. APP_URL localhost tidak bisa menerima webhook Collaborator; KOMERCE_WEBHOOK_SECRET masih kosong.\n";
}

echo json_encode([
    'ok' => $remoteStatus === 'PAID',
    'payment_id' => $paymentId,
    'order' => $order->number,
    'status' => $remoteStatus,
    'steps' => $steps,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

exit($remoteStatus === 'PAID' ? 0 : 2);
