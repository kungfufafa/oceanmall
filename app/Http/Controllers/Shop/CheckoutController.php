<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\CreateOrder;
use App\Actions\ZoneSessionManager;
use App\CheckoutSession;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Cart\CartManager;
use Shopper\Cart\CartSessionManager;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Models\PaymentTransaction;
use Shopper\Payment\Services\PaymentProcessingService;
use Stripe\StripeClient;
use Throwable;

final class CheckoutController extends Controller
{
    public function index(Request $request): RedirectResponse|Response
    {
        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('shop.cart');
        }

        $cart->load(['lines.purchasable.media']);
        $context = resolve(CartManager::class)->calculate($cart);

        $checkoutCartId = session()->get('checkout_cart_id');

        if ($checkoutCartId !== null && $checkoutCartId !== $cart->id) {
            session()->forget([CheckoutSession::KEY, 'stripe_payment', 'stripe_intent_id', 'checkout_cart_id']);
        }

        session()->put('checkout_cart_id', $cart->id);

        $checkout = session()->get(CheckoutSession::KEY, []);
        $shippingAddress = data_get($checkout, 'shipping_address');
        $shippingOption = data_get($checkout, 'shipping_option.0');
        $payment = data_get($checkout, 'payment.0');

        $deliveryOptions = [];

        if ($shippingAddress) {
            $packages = resolve(BuildShippingPackages::class)->handle();
            $deliveryOptions = resolve(FetchDeliveryRates::class)->handle($shippingAddress, $packages);
        }

        $paymentOptions = [];

        if ($countryId = data_get($shippingAddress, 'country_id')) {
            $paymentOptions = resolve(FetchPaymentMethods::class)->handle($countryId);
        }

        $maxStep = match (true) {
            (bool) $shippingOption => 3,
            (bool) $shippingAddress => 2,
            default => 1,
        };

        $requestedStep = (int) $request->integer('step', $maxStep);
        $step = min(max($requestedStep, 1), $maxStep);

        $stripeData = session()->get('stripe_payment');

        return Inertia::render('shop/checkout', [
            'cart' => $cart,
            'cartContext' => $context,
            'savedAddresses' => Auth::user()?->addresses()->with('country')->get() ?? [],
            'shippingAddress' => $shippingAddress,
            'deliveryOptions' => $deliveryOptions,
            'selectedDeliveryOption' => $shippingOption['id'] ?? null,
            'paymentOptions' => $paymentOptions,
            'selectedPaymentMethod' => $payment['id'] ?? null,
            'step' => $step,
            'stripeData' => $stripeData ? [
                'client_secret' => $stripeData['client_secret'],
                'publishable_key' => $stripeData['publishable_key'],
                'return_url' => route('shop.checkout.stripe-return'),
            ] : null,
        ]);
    }

    public function saveShippingAddress(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'street_address_plus' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
        ]);

        $zone = ZoneSessionManager::getSession();
        $data['country_id'] = $zone?->countryId;

        session()->put(CheckoutSession::SHIPPING_ADDRESS, $data);

        if ($cart = resolve(CartSessionManager::class)->current()) {
            resolve(CartManager::class)->addAddress($cart, AddressType::Shipping, [
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'address_1' => $data['street_address'],
                'address_2' => $data['street_address_plus'] ?? null,
                'postal_code' => $data['postal_code'],
                'city' => $data['city'],
                'phone' => $data['phone_number'] ?? null,
                'country_id' => $zone?->countryId,
            ]);
        }

        return redirect()->route('shop.checkout.index');
    }

    public function saveShippingOption(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'service_code' => ['required'],
        ]);

        $shippingAddress = session()->get(CheckoutSession::SHIPPING_ADDRESS);

        if (! $shippingAddress) {
            return redirect()->route('shop.checkout.index');
        }

        $packages = resolve(BuildShippingPackages::class)->handle();
        $deliveryOptions = resolve(FetchDeliveryRates::class)->handle($shippingAddress, $packages);

        $selected = collect($deliveryOptions)
            ->first(fn (array $option): bool => (string) $option['service_code'] === (string) $data['service_code']);

        if (! $selected) {
            return back()->withErrors(['service_code' => __('Selected option is no longer available.')]);
        }

        session()->forget(CheckoutSession::SHIPPING_OPTION);
        session()->push(CheckoutSession::SHIPPING_OPTION, [
            'id' => $selected['service_code'],
            'name' => $selected['service_name'],
            'price' => (int) $selected['amount'],
            'service_code' => $selected['service_code'],
            'carrier_code' => $selected['carrier_code'],
            'currency' => $selected['currency'],
            'estimated_days' => $selected['estimated_days'],
        ]);

        return redirect()->route('shop.checkout.index');
    }

    /**
     * Save payment method selection. If Stripe driver, create PaymentIntent (no order created yet).
     * Order is created only after Stripe confirmPayment succeeds (stripeReturn) or COD placeOrder.
     */
    public function preparePayment(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ]);

        [$selectedMethod, $error] = $this->resolveSelectedMethod($data['payment_method_id']);

        if ($error) {
            return $error;
        }

        session()->forget(CheckoutSession::PAYMENT);
        session()->push(CheckoutSession::PAYMENT, $selectedMethod);

        if (($selectedMethod['driver'] ?? null) !== 'stripe') {
            session()->forget(['stripe_payment', 'stripe_intent_id']);

            return redirect()->route('shop.checkout.index', ['step' => 3]);
        }

        $cart = resolve(CartSessionManager::class)->current();

        if (! $cart || $cart->lines->isEmpty()) {
            return redirect()->route('shop.cart');
        }

        $context = resolve(CartManager::class)->calculate($cart);

        $shippingOption = data_get(session()->get(CheckoutSession::KEY), 'shipping_option.0');
        $shippingPrice = (int) data_get($shippingOption, 'price', 0);

        $amount = (int) $context->total + $shippingPrice;

        try {
            $result = Payment::driver('stripe')->initiatePayment(
                amount: $amount,
                currency: $cart->currency_code,
                context: [
                    'metadata' => [
                        'cart_id' => $cart->id,
                        'customer_id' => Auth::id() ?? 0,
                    ],
                ],
            );
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['payment' => __('Unable to prepare payment. Please try again.')]);
        }

        if (! $result->success || ! $result->clientSecret) {
            return back()->withErrors(['payment' => $result->message ?? __('Payment preparation failed.')]);
        }

        $intentId = explode('_secret_', $result->clientSecret)[0] ?? null;

        session()->put('stripe_payment', [
            'client_secret' => $result->clientSecret,
            'publishable_key' => $result->data['publishable_key']
                ?? config('shopper.payment.drivers.stripe.credentials.publishable_key'),
        ]);
        session()->put('stripe_intent_id', $intentId);

        return redirect()->route('shop.checkout.index', ['step' => 3]);
    }

    /**
     * COD path: places the order immediately. Stripe path uses stripeReturn instead.
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ]);

        [$selectedMethod, $error] = $this->resolveSelectedMethod($data['payment_method_id']);

        if ($error) {
            return $error;
        }

        if (($selectedMethod['driver'] ?? null) === 'stripe') {
            return back()->withErrors(['payment' => __('Use the Stripe payment form to complete this order.')]);
        }

        session()->forget(CheckoutSession::PAYMENT);
        session()->push(CheckoutSession::PAYMENT, $selectedMethod);

        try {
            $order = resolve(CreateOrder::class)->handle();
            $result = resolve(PaymentProcessingService::class)->initiate($order);

            session()->forget(CheckoutSession::KEY);

            if (! $result->success) {
                return redirect()
                    ->route('shop.checkout.success', ['order' => $order->id])
                    ->with('error', $result->message ?? __('Payment initiation failed.'));
            }

            if ($result->redirectUrl) {
                return redirect()->away($result->redirectUrl);
            }

            return redirect()->route('shop.checkout.success', ['order' => $order->id]);
        } catch (Throwable $e) {
            report($e);

            return back()->withErrors(['order' => __('An error occurred while placing your order. Please try again.')]);
        }
    }

    /**
     * Stripe redirects here after confirmPayment. Verify intent succeeded, then create order.
     */
    public function stripeReturn(Request $request): RedirectResponse
    {
        $intentId = (string) $request->query('payment_intent', '');
        $redirectStatus = (string) $request->query('redirect_status', '');
        $sessionIntentId = session()->get('stripe_intent_id');

        if ($intentId === '' || $intentId !== $sessionIntentId) {
            return redirect()->route('shop.checkout.index')
                ->withErrors(['payment' => __('Invalid payment session.')]);
        }

        $lock = Cache::lock('stripe.return.'.$intentId, 15);

        try {
            $lock->block(10);
        } catch (LockTimeoutException) {
            return redirect()->route('shop.checkout.index')
                ->withErrors(['payment' => __('Your payment is still being processed. Please wait a moment.')]);
        }

        try {
            return $this->finalizeStripeReturn($intentId, $redirectStatus);
        } finally {
            $lock->release();
        }
    }

    private function finalizeStripeReturn(string $intentId, string $redirectStatus): RedirectResponse
    {
        $existingTransaction = PaymentTransaction::query()
            ->where('reference', $intentId)
            ->first();

        if ($existingTransaction) {
            session()->forget(['stripe_payment', 'stripe_intent_id', CheckoutSession::KEY, 'checkout_cart_id']);

            return redirect()->route('shop.checkout.success', ['order' => $existingTransaction->order_id]);
        }

        $intentStatus = $this->fetchStripeIntent($intentId);

        if (! in_array($intentStatus, ['succeeded', 'requires_capture', 'processing'], true)) {
            session()->forget(['stripe_payment', 'stripe_intent_id']);

            return redirect()->route('shop.checkout.index', ['step' => 3])
                ->withErrors(['payment' => __('Payment was not completed. Please try again.').' ('.($redirectStatus ?: 'unknown').')']);
        }

        try {
            $order = DB::transaction(function () use ($intentId, $intentStatus): Order {
                $order = resolve(CreateOrder::class)->handle();
                $this->attachStripeIntentToOrder($order, $intentId, $intentStatus);

                return $order;
            });
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('shop.cart')->withErrors(['order' => __('Order creation failed after payment.')]);
        }

        session()->forget(['stripe_payment', 'stripe_intent_id', CheckoutSession::KEY, 'checkout_cart_id']);
        resolve(CartSessionManager::class)->forget();

        return redirect()->route('shop.checkout.success', ['order' => $order->id]);
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: RedirectResponse|null}
     */
    private function resolveSelectedMethod(int $paymentMethodId): array
    {
        $shippingAddress = session()->get(CheckoutSession::SHIPPING_ADDRESS);
        $countryId = data_get($shippingAddress, 'country_id');

        if (! $countryId) {
            return [null, redirect()->route('shop.checkout.index')];
        }

        $paymentOptions = resolve(FetchPaymentMethods::class)->handle($countryId);
        $selected = collect($paymentOptions)
            ->first(fn (array $method): bool => $method['id'] === $paymentMethodId);

        if (! $selected) {
            return [null, back()->withErrors(['payment_method_id' => __('Selected payment method is no longer available.')])];
        }

        return [$selected, null];
    }

    private function attachStripeIntentToOrder(Order $order, string $intentId, ?string $status): void
    {
        $secret = (string) config('shopper.payment.drivers.stripe.credentials.secret_key');

        if ($secret !== '') {
            try {
                (new StripeClient($secret))->paymentIntents->update($intentId, [
                    'metadata' => ['order_id' => $order->id, 'order_number' => $order->number],
                ]);
            } catch (Throwable) {
                // Non-blocking
            }
        }

        $paymentStatus = match ($status) {
            'succeeded' => PaymentStatus::Paid,
            'requires_capture' => PaymentStatus::Authorized,
            default => PaymentStatus::Pending,
        };

        $order->update(['payment_status' => $paymentStatus]);

        $transactionStatus = match ($status) {
            'succeeded' => TransactionStatus::Success,
            default => TransactionStatus::Pending,
        };

        $transactionType = match ($status) {
            'succeeded' => TransactionType::Capture,
            'requires_capture' => TransactionType::Authorize,
            default => TransactionType::Initiate,
        };

        PaymentTransaction::query()->updateOrCreate(
            [
                'order_id' => $order->id,
                'reference' => $intentId,
            ],
            [
                'payment_method_id' => $order->payment_method_id,
                'driver' => 'stripe',
                'type' => $transactionType,
                'amount' => $order->price_amount,
                'currency_code' => $order->currency_code,
                'status' => $transactionStatus,
                'metadata' => ['stripe_status' => $status],
            ],
        );
    }

    private function fetchStripeIntent(string $intentId): ?string
    {
        $secret = (string) config('shopper.payment.drivers.stripe.credentials.secret_key');

        if ($secret === '') {
            return null;
        }

        try {
            return (new StripeClient($secret))
                ->paymentIntents
                ->retrieve($intentId)
                ->status;
        } catch (Throwable) {
            return null;
        }
    }
}
