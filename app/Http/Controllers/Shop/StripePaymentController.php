<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Models\Order;

final class StripePaymentController extends Controller
{
    public function __invoke(string $number): RedirectResponse|Response
    {
        $order = Order::query()
            ->where('number', $number)
            ->where('customer_id', auth()->id())
            ->firstOrFail();

        $stripePayment = session()->pull('stripe_payment');

        if (! $stripePayment) {
            return redirect()->route('shop.checkout.success', ['order' => $order->id]);
        }

        return Inertia::render('shop/stripe-payment', [
            'order' => $order,
            'clientSecret' => $stripePayment['client_secret'],
            'publishableKey' => $stripePayment['publishable_key'],
            'returnUrl' => route('shop.checkout.success', ['order' => $order->id]),
        ]);
    }
}
