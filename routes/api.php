<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\AddressController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CatalogController;
use App\Http\Controllers\Api\V1\CheckoutController;
use App\Http\Controllers\Api\V1\DestinationController;
use App\Http\Controllers\Api\V1\NotificationController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProfileController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/forgot-password', [ProfileController::class, 'forgotPassword'])->middleware('throttle:5,1');
    Route::post('auth/reset-password', [ProfileController::class, 'resetPassword'])->middleware('throttle:5,1');

    Route::get('catalog/home', [CatalogController::class, 'home']);
    Route::get('catalog/featured', [CatalogController::class, 'featured']);
    Route::get('catalog/promo', [CatalogController::class, 'promo']);
    Route::get('catalog/brands', [CatalogController::class, 'brands']);
    Route::get('catalog/collections', [CatalogController::class, 'collections']);
    Route::get('catalog/products', [CatalogController::class, 'products']);
    Route::get('catalog/products/{slug}', [CatalogController::class, 'product']);
    Route::get('catalog/search', [CatalogController::class, 'search'])->middleware('throttle:30,1');
    Route::get('catalog/categories', [CatalogController::class, 'categories']);
    Route::get('catalog/categories/{slug}', [CatalogController::class, 'category']);
    Route::get('catalog/collections/{slug}', [CatalogController::class, 'collection']);

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::patch('auth/profile', [ProfileController::class, 'update']);
        Route::put('auth/password', [ProfileController::class, 'updatePassword'])->middleware('throttle:6,1');

        Route::get('cart', [CartController::class, 'show']);
        Route::post('cart/items', [CartController::class, 'add'])->middleware('throttle:60,1');
        Route::patch('cart/items/{line}', [CartController::class, 'update'])->middleware('throttle:60,1');
        Route::delete('cart/items/{line}', [CartController::class, 'destroy']);
        Route::delete('cart', [CartController::class, 'clear']);
        Route::post('cart/coupon', [CartController::class, 'applyCoupon']);
        Route::delete('cart/coupon', [CartController::class, 'removeCoupon']);

        Route::get('checkout', [CheckoutController::class, 'show']);
        Route::get('checkout/destinations', DestinationController::class)->middleware('throttle:30,1');
        Route::post('checkout/shipping-address', [CheckoutController::class, 'saveShippingAddress']);
        Route::post('checkout/shipping-address/saved', [CheckoutController::class, 'applySavedAddress']);
        Route::post('checkout/shipping-option', [CheckoutController::class, 'saveShippingOption']);
        Route::post('checkout/place-order', [CheckoutController::class, 'placeOrder'])->middleware('throttle:20,1');

        Route::get('orders', [OrderController::class, 'index']);
        Route::get('orders/{number}', [OrderController::class, 'show']);
        Route::post('orders/{number}/retry-payment', [OrderController::class, 'retryPayment']);
        Route::post('orders/{number}/sync-payment', [OrderController::class, 'syncPayment'])->middleware('throttle:12,1');
        Route::post('orders/{number}/shipments/{shipment}/track', [OrderController::class, 'track']);
        Route::post('orders/{number}/confirm-received', [OrderController::class, 'confirmReceived']);

        Route::get('addresses', [AddressController::class, 'index']);
        Route::post('addresses', [AddressController::class, 'store']);
        Route::patch('addresses/{address}', [AddressController::class, 'update']);
        Route::delete('addresses/{address}', [AddressController::class, 'destroy']);
        Route::patch('addresses/{address}/default-shipping', [AddressController::class, 'setDefaultShipping']);
        Route::patch('addresses/{address}/default-billing', [AddressController::class, 'setDefaultBilling']);

        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
        Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);

        Route::post('catalog/products/{slug}/reviews', [CatalogController::class, 'storeReview'])
            ->middleware('throttle:10,1');
    });
});
