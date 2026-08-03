<?php

declare(strict_types=1);

use App\Http\Controllers\Account\AddressController;
use App\Http\Controllers\Account\OrderController as AccountOrderController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CategoryController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\CheckoutSuccessController;
use App\Http\Controllers\Shop\CollectionController;
use App\Http\Controllers\Shop\HomeController;
use App\Http\Controllers\Shop\ProductController;
use App\Http\Controllers\Shop\SearchController;
use App\Http\Controllers\Shop\StripePaymentController;
use App\Http\Controllers\Shop\ZoneController;
use App\Http\Controllers\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Storefront
Route::get('/', HomeController::class)->name('home');
Route::get('shop', [ProductController::class, 'index'])->name('shop.index');
Route::get('shop/{product:slug}', [ProductController::class, 'show'])->name('shop.product');
Route::get('categories', [CategoryController::class, 'index'])->name('shop.categories');
Route::get('categories/{category:slug}', [CategoryController::class, 'show'])->name('shop.category');
Route::get('collections/{collection:slug}', [CollectionController::class, 'show'])->name('shop.collection');
Route::get('search', SearchController::class)->middleware('throttle:30,1')->name('shop.search');

// Cart
Route::get('cart', [CartController::class, 'index'])->name('shop.cart');
Route::middleware('throttle:60,1')->group(function (): void {
    Route::post('cart', [CartController::class, 'add'])->name('shop.cart.add');
    Route::patch('cart/{line}', [CartController::class, 'update'])->name('shop.cart.update');
    Route::delete('cart/{line}', [CartController::class, 'destroy'])->name('shop.cart.destroy');
    Route::delete('cart', [CartController::class, 'clear'])->name('shop.cart.clear');
});

// Zone
Route::patch('zone', [ZoneController::class, 'update'])->middleware('throttle:30,1')->name('shop.zone.update');

// Checkout
Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('checkout', [CheckoutController::class, 'index'])->name('shop.checkout.index');
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

// Account
Route::middleware(['auth', 'verified'])->prefix('account')->name('account.')->group(function (): void {
    Route::get('orders', [AccountOrderController::class, 'index'])->name('orders');
    Route::get('orders/{order}', [AccountOrderController::class, 'show'])->name('orders.show');

    Route::get('addresses', [AddressController::class, 'index'])->name('addresses');
    Route::post('addresses', [AddressController::class, 'store'])->name('addresses.store');
    Route::patch('addresses/{address}', [AddressController::class, 'update'])->name('addresses.update');
    Route::delete('addresses/{address}', [AddressController::class, 'destroy'])->name('addresses.destroy');
    Route::patch('addresses/{address}/default-shipping', [AddressController::class, 'setDefaultShipping'])->name('addresses.default-shipping');
    Route::patch('addresses/{address}/default-billing', [AddressController::class, 'setDefaultBilling'])->name('addresses.default-billing');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
