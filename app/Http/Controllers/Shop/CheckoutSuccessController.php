<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Models\Order;

final class CheckoutSuccessController extends Controller
{
    public function __invoke(Order $order): Response
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        $komercePayment = session()->get('komerce_payment');

        session()->forget(['stripe_payment', 'stripe_order_number', 'checkout_cart_id', \App\CheckoutSession::KEY, 'komerce_payment']);
        resolve(\Shopper\Cart\CartSessionManager::class)->forget();

        return Inertia::render('shop/checkout-success', [
            'order' => $order,
            'komercePayment' => $komercePayment,
        ]);
    }
}
