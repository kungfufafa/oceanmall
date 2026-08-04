<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Checkout\ResolveKomercePaymentInstructions;
use App\CheckoutSession;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class CheckoutSuccessController extends Controller
{
    public function __invoke(Order $order): Response
    {
        abort_unless($order->customer_id === auth()->id(), 403);

        $order->refresh();

        $komercePayment = null;

        if ($order->payment_status !== PaymentStatus::Paid) {
            $komercePayment = session()->pull('komerce_payment')
                ?? resolve(ResolveKomercePaymentInstructions::class)->handle($order);
        } else {
            session()->forget('komerce_payment');
        }

        // Clear leftover checkout/payment session only — do NOT wipe the live cart.
        // Cart is already completed (or forgotten) at place-order time. Re-forgetting
        // here would destroy items added while the success page is still open (polls).
        session()->forget([
            'stripe_payment',
            'stripe_order_number',
            'checkout_cart_id',
            CheckoutSession::KEY,
        ]);

        return Inertia::render('shop/checkout-success', [
            'order' => $order->only([
                'id',
                'number',
                'price_amount',
                'currency_code',
                'status',
                'payment_status',
            ]),
            'komercePayment' => $komercePayment,
        ]);
    }
}
