<?php

declare(strict_types=1);

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\ConfirmOrderReceivedController;
use App\Http\Controllers\Account\NotificationController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Account\RetryKomercePaymentController;
use App\Http\Controllers\Account\SyncKomercePaymentStatusController;
use App\Http\Controllers\Account\TrackShipmentController;
use App\Http\Controllers\Cpanel\OverrideAllocationController;
use App\Http\Controllers\Cpanel\PrintShipmentLabelController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CartCouponController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\CheckoutSuccessController;
use App\Http\Controllers\Shop\CollectionController;
use App\Http\Controllers\Shop\DestinationSearchController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\ProductReviewController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Controllers\Shop\StripePaymentController;
use App\Http\Controllers\Shop\ZoneController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\Webhooks\KomerceDeliveryWebhookController;
use App\Http\Controllers\Webhooks\KomercePaymentWebhookController;
use App\Http\Controllers\Webhooks\KomerceQrislyWebhookController;
use Illuminate\Support\Facades\Route;

// Storefront
Route::get('/', HomeController::class)->name('home');

// PWA assets & fallback routes
Route::get('manifest.json', function () {
    return response()->file(public_path('build/manifest.webmanifest'), [
        'Content-Type' => 'application/manifest+json',
    ]);
});
Route::get('manifest.webmanifest', function () {
    return response()->file(public_path('build/manifest.webmanifest'), [
        'Content-Type' => 'application/manifest+json',
    ]);
});
Route::get('sw.js', function () {
    return response()->file(public_path('build/sw.js'), [
        'Content-Type' => 'application/javascript',
        'Service-Worker-Allowed' => '/',
    ]);
});
Route::get('shop', [ProductController::class, 'index'])->name('shop.index');
Route::get('shop/{product:slug}', [ProductController::class, 'show'])->name('shop.product');
Route::post('shop/{product:slug}/reviews', [ProductReviewController::class, 'store'])
    ->middleware(['auth', 'verified', 'throttle:10,1'])
    ->name('shop.product.reviews.store');
Route::get('categories', [CategoryController::class, 'index'])->name('shop.categories');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('shop.category');
Route::get('collections/{collection:slug}', [CollectionController::class, 'show'])->name('shop.collection');
Route::get('search', SearchController::class)->middleware('throttle:30,1')->name('shop.search');

// Cart
Route::get('cart', [CartController::class, 'index'])->name('shop.cart');
Route::middleware('throttle:60,1')->group(function (): void {
    Route::post('cart', [CartController::class, 'add'])->name('shop.cart.add');
    Route::post('cart/coupon', [CartCouponController::class, 'store'])->name('shop.cart.coupon.store');
    Route::delete('cart/coupon', [CartCouponController::class, 'destroy'])->name('shop.cart.coupon.destroy');
    Route::patch('cart/{line}', [CartController::class, 'update'])->name('shop.cart.update');
    Route::delete('cart/{line}', [CartController::class, 'destroy'])->name('shop.cart.destroy');
    Route::delete('cart', [CartController::class, 'clear'])->name('shop.cart.clear');
});

// Zone
Route::patch('zone', [ZoneController::class, 'update'])->middleware('throttle:30,1')->name('shop.zone.update');

// Checkout
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('checkout', [CheckoutController::class, 'index'])->name('shop.checkout.index');
    Route::get('checkout/destinations', DestinationSearchController::class)
        ->middleware('throttle:30,1')
        ->name('shop.checkout.destinations');
    Route::post('checkout/shipping-address', [CheckoutController::class, 'saveShippingAddress'])->name('shop.checkout.shipping-address');
    Route::post('checkout/shipping-option', [CheckoutController::class, 'saveShippingOption'])->name('shop.checkout.shipping-option');
    Route::post('checkout/prepare-payment', [CheckoutController::class, 'preparePayment'])->name('shop.checkout.prepare-payment');
    Route::post('checkout/place-order', [CheckoutController::class, 'placeOrder'])->name('shop.checkout.place-order');
    Route::get('checkout/stripe-return', [CheckoutController::class, 'stripeReturn'])->name('shop.checkout.stripe-return');
    Route::get('checkout/payment/{number}', StripePaymentController::class)->name('shop.checkout.stripe');
    Route::get('checkout/success/{order}', CheckoutSuccessController::class)->name('shop.checkout.success');
});

// Webhooks
Route::post('webhooks/stripe', StripeWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.stripe');

Route::post('webhooks/komerce/payment', KomercePaymentWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.komerce.payment');

Route::post('webhooks/komerce/delivery', KomerceDeliveryWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.komerce.delivery');

Route::post('webhooks/komerce/qrisly', KomerceQrislyWebhookController::class)
    ->middleware('throttle:60,1')
    ->name('webhooks.komerce.qrisly');

// Account
Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function (): void {
    Route::get('orders', [AccountOrderController::class, 'index'])->name('orders');
    Route::get('orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');
    Route::post('orders/{order}/retry-payment', RetryKomercePaymentController::class)
        ->name('orders.retry-payment');
    Route::post('orders/{order}/sync-payment', SyncKomercePaymentStatusController::class)
        ->middleware('throttle:12,1')
        ->name('orders.sync-payment');
    Route::post('orders/{order}/shipments/{shipment}/track', TrackShipmentController::class)
        ->name('orders.shipments.track');
    Route::post('orders/{order}/confirm-received', ConfirmOrderReceivedController::class)
        ->name('orders.confirm-received');

    Route::get('notifications', [NotificationController::class, 'index'])->name('notifications');
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');

    Route::get('addresses', [AddressController::class, 'index'])->name('addresses');
    Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::patch('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::patch('addresses/{address}/default-shipping', [AddressController::class, 'setDefaultShipping'])->name('addresses.default-shipping');
    Route::patch('addresses/{address}/default-billing', [AddressController::class, 'setDefaultBilling'])->name('addresses.default-billing');
});

// Warehouse fulfillment under Shopper /cpanel only — no separate /admin backoffice.
Route::middleware(['auth', 'verified'])
    ->prefix(config('shopper.admin.prefix', 'cpanel'))
    ->group(function (): void {
        Route::get('orders/{order}/fulfillment/label', PrintShipmentLabelController::class)
            ->name('shopper.orders.fulfillment.print-label');
        Route::post('orders/{order}/fulfillment/override-allocation', OverrideAllocationController::class)
            ->name('shopper.orders.fulfillment.override-allocation');
    });

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('dashboard', \App\Http\Controllers\DashboardController::class)->name('dashboard');
});

require __DIR__.'/settings.php';
