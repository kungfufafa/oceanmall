<?php

declare(strict_types=1);

namespace App\Actions;

use App\CheckoutSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Shopper\Cart\Actions\CreateOrderFromCartAction;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;

final class CreateOrder
{
    public function handle(): Order
    {
        $checkout = session()->get(CheckoutSession::KEY);

        abort_unless(
            $checkout
            && data_get($checkout, 'shipping_option')
            && data_get($checkout, 'payment'),
            422,
            __('Checkout session is incomplete or expired.'),
        );

        $cart = cartSession();

        abort_if(
            Auth::check() && $cart->customer_id !== null && $cart->customer_id !== Auth::id(),
            403,
        );

        if (Auth::check() && $cart->customer_id === null) {
            $cart->update(['customer_id' => Auth::id()]);
        }

        $lock = Cache::lock('checkout.create-order.'.$cart->id, 10);

        abort_unless($lock->get(), 409, __('A checkout is already in progress.'));

        try {
            return DB::transaction(function () use ($cart, $checkout): Order {
                $order = resolve(CreateOrderFromCartAction::class)->execute($cart);

                $shippingPrice = (int) data_get($checkout, 'shipping_option.0.price', 0);

                $order->update([
                    'shipping_option_id' => data_get($checkout, 'shipping_option.0.id'),
                    'payment_method_id' => data_get($checkout, 'payment.0.id'),
                    'price_amount' => $order->price_amount + $shippingPrice,
                ]);

                $this->storeKomercePaymentReference($order, $checkout);

                return $order;
            });
        } finally {
            $lock->release();
        }
    }

    private function storeKomercePaymentReference(Order $order, mixed $checkout): void
    {
        $reference = data_get($checkout, 'komerce_payment_ref')
            ?? data_get($checkout, 'payment.0.komerce_payment_ref')
            ?? data_get($checkout, 'payment.0.payment_id');

        if (! is_string($reference) || trim($reference) === '') {
            return;
        }

        PaymentTransaction::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'reference' => trim($reference),
            ],
            [
                'payment_method_id' => $order->payment_method_id,
                'driver' => 'komerce',
                'type' => TransactionType::Initiate,
                'amount' => (int) $order->price_amount,
                'currency_code' => $order->currency_code,
                'status' => TransactionStatus::Pending,
                'metadata' => ['komerce_payment_ref' => trim($reference)],
            ],
        );
    }
}
