<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\CreateKomercePayment;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\Checkout\PersistUserShippingAddress;
use App\Actions\CreateOrder;
use App\Actions\Notify\NotifyOrderCustomer;
use App\Actions\Warehouse\SuggestAllocation;
use App\Actions\ZoneSessionManager;
use App\CheckoutSession;
use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use App\Enums\OrderNotificationType;
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

        $persistAddress = resolve(PersistUserShippingAddress::class);

        // Returning customers: reuse default saved address (incl. district)
        // so they land on shipping rates instead of retyping the form.
        if (! is_array($shippingAddress) || $shippingAddress === []) {
            $user = Auth::user();
            if ($user) {
                $defaultAddress = $user->addresses()
                    ->where('shipping_default', true)
                    ->orderByDesc('updated_at')
                    ->first()
                    ?? $user->addresses()->orderByDesc('updated_at')->first();

                if ($defaultAddress) {
                    $applied = $persistAddress->toCheckoutShippingAddress($defaultAddress);
                    if ($applied !== null) {
                        session()->put(CheckoutSession::SHIPPING_ADDRESS, $applied);
                        $shippingAddress = $applied;
                        $checkout = session()->get(CheckoutSession::KEY, []);
                    }
                }
            }
        }

        $deliveryOptions = [];
        $allocation = null;
        $deliveryOptionsByShipment = [];

        if ($shippingAddress) {
            $packages = resolve(BuildShippingPackages::class)->handle();

            [$allocation, $deliveryOptionsByShipment] = $this->resolveAllocationAndRates(
                $cart,
                $shippingAddress,
                $packages,
                $checkout,
            );

            if (count($deliveryOptionsByShipment) === 1) {
                $deliveryOptions = reset($deliveryOptionsByShipment) ?: [];
            } elseif (count($deliveryOptionsByShipment) === 0) {
                $deliveryOptions = resolve(FetchDeliveryRates::class)->handle($shippingAddress, $packages);
            }
        }

        $paymentOptions = [];
        $countryId = data_get($shippingAddress, 'country_id')
            ?? ZoneSessionManager::ensureSession()?->countryId;

        if ($countryId && is_array($shippingAddress) && ! data_get($shippingAddress, 'country_id')) {
            $shippingAddress['country_id'] = $countryId;
            session()->put(CheckoutSession::SHIPPING_ADDRESS, $shippingAddress);
        }

        if ($countryId) {
            $paymentOptions = resolve(FetchPaymentMethods::class)->handle((int) $countryId);
        }

        $maxStep = match (true) {
            (bool) $shippingOption => 3,
            (bool) $shippingAddress => 2,
            default => 1,
        };

        $requestedStep = (int) $request->integer('step', $maxStep);
        $step = min(max($requestedStep, 1), $maxStep);

        $stripeData = session()->get('stripe_payment');
        $komercePayment = session()->get('komerce_payment');

        $selectedByShipment = data_get($checkout, 'shipping_options_by_shipment', []);

        $user = Auth::user();

        return Inertia::render('shop/checkout', [
            'cart' => $cart,
            'cartContext' => $context,
            'savedAddresses' => $user
                ? $persistAddress->mapSavedAddressesForCheckout($user)
                : [],
            'shippingAddress' => $shippingAddress,
            'deliveryOptions' => $deliveryOptions,
            'selectedDeliveryOption' => $shippingOption['id'] ?? null,
            'allocation' => $allocation,
            'deliveryOptionsByShipment' => $deliveryOptionsByShipment,
            'selectedRatesByShipment' => collect($selectedByShipment)->map(
                fn (mixed $rate): string => is_array($rate) ? (string) ($rate['service_code'] ?? '') : (string) $rate
            )->all(),
            'paymentOptions' => $paymentOptions,
            'selectedPaymentMethod' => $payment['id'] ?? null,
            'step' => $step,
            'stripeData' => $stripeData && config('shopper.payment.drivers.stripe.enabled', false) ? [
                'client_secret' => $stripeData['client_secret'],
                'publishable_key' => $stripeData['publishable_key'],
                'return_url' => route('shop.checkout.stripe-return'),
            ] : null,
            'komercePayment' => $komercePayment,
            'komerceEnabled' => komerce_enabled(),
            'shippingRatesHint' => $this->shippingRatesHint(
                is_array($shippingAddress) ? $shippingAddress : null,
                $allocation,
                $deliveryOptions,
                $deliveryOptionsByShipment,
            ),
            'couponCode' => $cart->coupon_code,
        ]);
    }

    /**
     * Actionable copy when step 2 has no courier options.
     *
     * @param  array<string, mixed>|null  $shippingAddress
     * @param  list<array<string, mixed>>|null  $allocation
     * @param  list<array<string, mixed>>  $deliveryOptions
     * @param  array<int|string, list<array<string, mixed>>>  $deliveryOptionsByShipment
     */
    private function shippingRatesHint(
        ?array $shippingAddress,
        ?array $allocation,
        array $deliveryOptions,
        array $deliveryOptionsByShipment,
    ): ?string {
        if ($shippingAddress === null) {
            return null;
        }

        $hasAnyRate = $deliveryOptions !== []
            || collect($deliveryOptionsByShipment)->contains(fn (mixed $opts): bool => is_array($opts) && $opts !== []);

        if ($hasAnyRate) {
            return null;
        }

        if (! komerce_enabled()) {
            return __('Pengiriman Komerce/RajaOngkir belum dikonfigurasi. Hubungi admin toko.');
        }

        if (blank(data_get($shippingAddress, 'rajaongkir_destination_id'))) {
            return __('Pilih kecamatan/destinasi dari pencarian agar ongkir bisa dihitung.');
        }

        if ($allocation === null || $allocation === []) {
            return __('Tidak ada gudang dengan origin RajaOngkir yang bisa memenuhi stok keranjangmu. Hubungi admin toko.');
        }

        return __('Tidak ada opsi kurir untuk destinasi ini. Coba ubah alamat atau hubungi admin toko.');
    }

    public function saveShippingAddress(Request $request): RedirectResponse
    {
        $destinationRule = komerce_enabled()
            ? ['required', 'string', 'max:50']
            : ['nullable', 'string', 'max:50'];

        // Coerce numeric destination ids from JSON/Inertia before string rules.
        $request->merge([
            'rajaongkir_destination_id' => $this->normalizeDestinationId(
                $request->input('rajaongkir_destination_id') ?? $request->input('destination_id'),
            ),
            'destination_id' => $this->normalizeDestinationId($request->input('destination_id')),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'street_address_plus' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['nullable', 'string', 'max:20'],
            'rajaongkir_destination_id' => $destinationRule,
            'rajaongkir_destination_label' => ['nullable', 'string', 'max:255'],
            'destination_id' => ['nullable', 'string', 'max:50'],
        ]);

        $zone = ZoneSessionManager::ensureSession();
        $data['country_id'] = $zone?->countryId;

        $destinationId = $data['rajaongkir_destination_id'] ?? $data['destination_id'] ?? null;
        if ($destinationId !== null && $destinationId !== '') {
            $data['rajaongkir_destination_id'] = (string) $destinationId;
        }

        if (! $data['country_id']) {
            return back()->withErrors([
                'city' => 'Wilayah pengiriman belum tersedia. Muat ulang halaman lalu coba lagi.',
            ]);
        }

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

        if ($user = Auth::user()) {
            $saved = resolve(PersistUserShippingAddress::class)->handle($user, $data);
            $data['saved_address_id'] = $saved->id;
            session()->put(CheckoutSession::SHIPPING_ADDRESS, $data);
        }

        return redirect()->route('shop.checkout.index');
    }

    public function saveShippingOption(Request $request): RedirectResponse
    {
        $shippingAddress = session()->get(CheckoutSession::SHIPPING_ADDRESS);

        if (! $shippingAddress) {
            return redirect()->route('shop.checkout.index');
        }

        if ($request->has('rates') && is_array($request->input('rates'))) {
            return $this->saveShippingOptionsByShipment($request, $shippingAddress);
        }

        $data = $request->validate([
            'service_code' => ['required'],
        ]);

        $allocationPlan = session()->get(CheckoutSession::ALLOCATION_PLAN);
        $singleShipment = ($allocationPlan instanceof AllocationPlan && count($allocationPlan->shipments) === 1)
            ? $allocationPlan->shipments[0]
            : null;

        $buildPackages = resolve(BuildShippingPackages::class);
        $packages = $singleShipment !== null
            ? $buildPackages->handleFromLines($singleShipment->lines)
            : $buildPackages->handle();

        $originInventoryId = $singleShipment?->inventory_id;
        $deliveryOptions = resolve(FetchDeliveryRates::class)->handle($shippingAddress, $packages, $originInventoryId);

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
     * Save per-shipment rates (multi-package path).
     *
     * @param  array<string, mixed>  $shippingAddress
     */
    private function saveShippingOptionsByShipment(Request $request, array $shippingAddress): RedirectResponse
    {
        $data = $request->validate([
            'rates' => ['required', 'array'],
            'rates.*' => ['required', 'string'],
        ]);

        $cart = resolve(\Shopper\Cart\CartSessionManager::class)->current();

        if (! $cart) {
            return redirect()->route('shop.cart');
        }

        $buildPackages = resolve(BuildShippingPackages::class);
        $fetchRates = resolve(FetchDeliveryRates::class);

        $allocationPlan = session()->get(CheckoutSession::ALLOCATION_PLAN);

        if (! $allocationPlan instanceof AllocationPlan) {
            try {
                $allocationPlan = resolve(SuggestAllocation::class)->handle($cart, $shippingAddress);
                session()->put(CheckoutSession::ALLOCATION_PLAN, $allocationPlan);
            } catch (\Throwable) {
                return back()->withErrors(['rates' => __('Unable to determine shipment allocation.')]);
            }
        }

        $selectedRates = [];
        $totalPrice = 0;

        foreach ($allocationPlan->shipments as $shipmentDraft) {
            $inventoryId = $shipmentDraft->inventory_id;
            $serviceCode = $data['rates'][$inventoryId] ?? $data['rates'][(string) $inventoryId] ?? null;

            if (! $serviceCode) {
                return back()->withErrors(['rates' => __('Please select a delivery option for all packages.')]);
            }

            $shipmentPackages = $buildPackages->handleFromLines($shipmentDraft->lines);
            $options = $fetchRates->handle($shippingAddress, $shipmentPackages, $inventoryId);
            $selected = collect($options)
                ->first(fn (array $o): bool => (string) $o['service_code'] === (string) $serviceCode);

            if (! $selected) {
                return back()->withErrors(['rates' => __('Selected option is no longer available.')]);
            }

            $selectedRates[$inventoryId] = [
                'id' => $selected['service_code'],
                'service_code' => $selected['service_code'],
                'carrier_code' => $selected['carrier_code'],
                'carrier_name' => $selected['carrier_name'] ?? null,
                'service_name' => $selected['service_name'],
                'amount' => (int) $selected['amount'],
                'currency' => $selected['currency'],
                'estimated_days' => $selected['estimated_days'] ?? null,
            ];

            $totalPrice += (int) $selected['amount'];
        }

        session()->put(CheckoutSession::SHIPPING_OPTIONS_BY_SHIPMENT, $selectedRates);

        session()->forget(CheckoutSession::SHIPPING_OPTION);
        session()->push(CheckoutSession::SHIPPING_OPTION, [
            'id' => 'split-shipment',
            'name' => 'Split shipment',
            'price' => $totalPrice,
            'service_code' => 'split-shipment',
            'carrier_code' => 'multi',
            'currency' => 'IDR',
            'estimated_days' => null,
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

        [$selectedMethod, $error] = $this->resolveSelectedMethod((int) $data['payment_method_id']);

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
     * COD / Komerce path: places the order immediately. Stripe path uses stripeReturn instead.
     */
    public function placeOrder(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ]);

        [$selectedMethod, $error] = $this->resolveSelectedMethod((int) $data['payment_method_id']);

        if ($error) {
            return $error;
        }

        if (($selectedMethod['driver'] ?? null) === 'stripe') {
            return back()->withErrors(['payment' => __('Use the Stripe payment form to complete this order.')]);
        }

        session()->forget(CheckoutSession::PAYMENT);
        session()->push(CheckoutSession::PAYMENT, $selectedMethod);

        if (($selectedMethod['driver'] ?? null) === 'komerce') {
            return $this->placeKomerceOrder($selectedMethod);
        }

        try {
            $order = resolve(CreateOrder::class)->handle();
            resolve(NotifyOrderCustomer::class)->handle($order, OrderNotificationType::AwaitingPayment);
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
     * Create Komerce VA/QRIS charge after order creation, store instructions in session.
     *
     * @param  array<string, mixed>  $selectedMethod
     */
    private function placeKomerceOrder(array $selectedMethod): RedirectResponse
    {
        $order = null;

        try {
            $order = resolve(CreateOrder::class)->handle();
            resolve(NotifyOrderCustomer::class)->handle($order, OrderNotificationType::AwaitingPayment);
            $instructions = resolve(CreateKomercePayment::class)->handle($order, $selectedMethod);

            session()->forget(CheckoutSession::KEY);
            session()->put('komerce_payment', $instructions);

            resolve(CartSessionManager::class)->forget();

            return redirect()->route('shop.checkout.success', ['order' => $order->id]);
        } catch (Throwable $e) {
            report($e);

            if ($order !== null) {
                // Order owns reserved stock — clear cart/checkout so the customer
                // retries payment on the account order instead of re-ordering.
                session()->forget(CheckoutSession::KEY);
                resolve(CartSessionManager::class)->forget();

                return redirect()
                    ->route('account.orders.show', ['order' => $order->id])
                    ->with('error', __('Your order was placed but payment setup failed. You can retry payment from this order page.'));
            }

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
            resolve(NotifyOrderCustomer::class)->handle($order, OrderNotificationType::Paid);
        } catch (Throwable $e) {
            report($e);

            return redirect()->route('shop.cart')->withErrors(['order' => __('Order creation failed after payment.')]);
        }

        session()->forget(['stripe_payment', 'stripe_intent_id', CheckoutSession::KEY, 'checkout_cart_id']);
        resolve(CartSessionManager::class)->forget();

        return redirect()->route('shop.checkout.success', ['order' => $order->id]);
    }

    /**
     * Run SuggestAllocation, store plan in session, return [allocationArray, deliveryOptionsByShipment].
     *
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<int, mixed>  $packages
     * @param  array<string, mixed>  $checkout
     * @return array{0: array<string, mixed>|null, 1: array<int|string, array<int, array<string, mixed>>>}
     */
    private function resolveAllocationAndRates(
        \Shopper\Cart\Models\Cart $cart,
        array $shippingAddress,
        array $packages,
        array $checkout,
    ): array {
        try {
            $plan = resolve(SuggestAllocation::class)->handle($cart, $shippingAddress);
        } catch (\Throwable) {
            return [null, []];
        }

        session()->put(CheckoutSession::ALLOCATION_PLAN, $plan);

        $allocationArray = array_map(
            static fn (ShipmentDraft $draft): array => [
                'inventory_id' => $draft->inventory_id,
                'lines' => $draft->lines,
            ],
            $plan->shipments,
        );

        $inventoryIds = array_column($allocationArray, 'inventory_id');
        $inventories = \Shopper\Core\Models\Inventory::query()
            ->whereIn('id', $inventoryIds)
            ->get()
            ->keyBy('id');

        foreach ($allocationArray as &$draft) {
            $inv = $inventories->get($draft['inventory_id']);
            $draft['inventory_name'] = $inv?->getAttribute('name') ?? (string) $draft['inventory_id'];
        }
        unset($draft);

        $fetchRates = resolve(FetchDeliveryRates::class);
        $buildPackages = resolve(BuildShippingPackages::class);
        $deliveryOptionsByShipment = [];

        foreach ($plan->shipments as $shipmentDraft) {
            $shipmentPackages = $buildPackages->handleFromLines($shipmentDraft->lines);
            $rates = $fetchRates->handle($shippingAddress, $shipmentPackages, $shipmentDraft->inventory_id);
            $deliveryOptionsByShipment[$shipmentDraft->inventory_id] = $rates;
        }

        return [$allocationArray, $deliveryOptionsByShipment];
    }

    /**
     * @return array{0: array<string, mixed>|null, 1: RedirectResponse|null}
     */
    private function resolveSelectedMethod(int $paymentMethodId): array
    {
        $shippingAddress = session()->get(CheckoutSession::SHIPPING_ADDRESS);
        $countryId = data_get($shippingAddress, 'country_id')
            ?? ZoneSessionManager::ensureSession()?->countryId;

        if (! $countryId) {
            return [null, redirect()->route('shop.checkout.index')];
        }

        $paymentOptions = resolve(FetchPaymentMethods::class)->handle((int) $countryId);
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

    private function normalizeDestinationId(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || is_float($value) || is_string($value)) {
            $normalized = trim((string) $value);

            return $normalized === '' ? null : $normalized;
        }

        return null;
    }
}
