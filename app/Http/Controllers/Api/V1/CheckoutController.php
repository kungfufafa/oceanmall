<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\CreateKomercePayment;
use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Checkout\FetchPaymentMethods;
use App\Actions\Checkout\PersistUserShippingAddress;
use App\Actions\CreateOrder;
use App\Actions\GetCountriesByZone;
use App\Actions\Notify\NotifyOrderCustomer;
use App\Actions\Warehouse\SuggestAllocation;
use App\DTO\CountryByZoneData;
use App\Enums\OrderNotificationType;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CustomerCart;
use App\Support\CustomerCatalogPresenter;
use App\Support\CustomerCheckoutState;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shopper\Cart\CartManager;
use Shopper\Cart\Models\Cart;
use Shopper\Core\Enum\AddressType;
use Shopper\Core\Models\PaymentMethod;
use Throwable;

final class CheckoutController extends Controller
{
    public function __construct(
        private readonly CustomerCart $customerCart,
        private readonly CustomerCheckoutState $checkoutState,
        private readonly CustomerCatalogPresenter $presenter,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $cart = $this->customerCart->current($user)->load(['lines.purchasable.media']);
        $state = $this->checkoutState->get($user);
        $address = is_array($state['shipping_address'] ?? null) ? $state['shipping_address'] : null;

        $rates = [];
        if (is_array($address) && $cart->lines->isNotEmpty()) {
            $rates = $this->ratesForCart($cart, $address);
        }

        $countryId = is_array($address) ? (int) ($address['country_id'] ?? 0) : 0;
        if ($countryId < 1) {
            $countryId = (int) ($this->defaultCountry()?->countryId ?? 0);
        }

        $methods = $countryId > 0 ? resolve(FetchPaymentMethods::class)->handle($countryId) : [];

        $context = $cart->lines->isEmpty() ? null : resolve(CartManager::class)->calculate($cart);

        return response()->json([
            'data' => [
                'cart' => $this->presenter->cart($cart, $context),
                'shipping_address' => $address,
                'shipping_option' => $state['shipping_option'][0] ?? null,
                'shipping_rates' => $rates,
                'payment_methods' => $methods,
                'payment' => $state['payment'][0] ?? null,
                'saved_addresses' => resolve(PersistUserShippingAddress::class)->mapSavedAddressesForCheckout($user),
            ],
        ]);
    }

    public function applySavedAddress(Request $request): JsonResponse
    {
        $data = $request->validate([
            'address_id' => ['required', 'integer'],
        ]);

        $user = $this->customer($request);
        $address = $user->addresses()->whereKey($data['address_id'])->firstOrFail();
        $mapped = resolve(PersistUserShippingAddress::class)->toCheckoutShippingAddress($address);
        if ($mapped === null) {
            return response()->json([
                'message' => 'Alamat belum punya kecamatan RajaOngkir. Pilih kecamatan dulu.',
            ], 422);
        }

        $this->checkoutState->putShippingAddress($user, $mapped);

        return $this->show($request);
    }

    public function saveShippingAddress(Request $request): JsonResponse
    {
        $destinationRule = komerce_shipping_cost_enabled()
            ? ['required', 'string', 'max:50']
            : ['nullable', 'string', 'max:50'];

        $request->merge([
            'rajaongkir_destination_id' => $this->normalizeDestinationId(
                $request->input('rajaongkir_destination_id') ?? $request->input('destination_id'),
            ),
        ]);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'street_address' => ['required', 'string', 'max:255'],
            'street_address_plus' => ['nullable', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'city' => ['required', 'string', 'max:255'],
            'state' => ['nullable', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'max:20'],
            'rajaongkir_destination_id' => $destinationRule,
            'rajaongkir_destination_label' => ['nullable', 'string', 'max:255'],
            'rajaongkir_pin_point' => ['nullable', 'string', 'max:64'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
        ]);

        $zone = $this->defaultCountry();
        $data['country_id'] = $zone?->countryId;
        if (! $data['country_id']) {
            return response()->json(['message' => 'Wilayah pengiriman belum tersedia.'], 422);
        }

        if (filled($data['latitude'] ?? null) && filled($data['longitude'] ?? null) && blank($data['rajaongkir_pin_point'] ?? null)) {
            $data['rajaongkir_pin_point'] = $data['latitude'].','.$data['longitude'];
        }

        $user = $this->customer($request);
        $saved = resolve(PersistUserShippingAddress::class)->handle($user, $data);
        $data['saved_address_id'] = $saved->id;
        $this->checkoutState->putShippingAddress($user, $data);

        $cart = $this->customerCart->current($user);
        resolve(CartManager::class)->addAddress($cart, AddressType::Shipping, [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'address_1' => $data['street_address'],
            'address_2' => $data['street_address_plus'] ?? null,
            'postal_code' => $data['postal_code'],
            'city' => $data['city'],
            'phone' => $data['phone_number'] ?? null,
            'country_id' => $data['country_id'],
        ]);

        return $this->show($request);
    }

    public function saveShippingOption(Request $request): JsonResponse
    {
        $user = $this->customer($request);
        $state = $this->checkoutState->get($user);
        $address = $state['shipping_address'] ?? null;
        if (! is_array($address)) {
            return response()->json(['message' => 'Alamat pengiriman belum diisi.'], 422);
        }

        $data = $request->validate([
            'service_code' => ['required', 'string'],
        ]);

        $cart = $this->customerCart->current($user);
        $rates = $this->ratesForCart($cart, $address);
        $selected = collect($rates)->first(
            fn (array $option): bool => (string) $option['service_code'] === (string) $data['service_code'],
        );

        if (! is_array($selected)) {
            return response()->json(['message' => 'Opsi pengiriman tidak tersedia.'], 422);
        }

        $this->checkoutState->putShippingOption($user, [[
            'id' => $selected['service_code'],
            'name' => $selected['service_name'],
            'price' => (int) $selected['amount'],
            'service_code' => $selected['service_code'],
            'service_name' => $selected['service_name'],
            'carrier_code' => $selected['carrier_code'],
            'carrier_name' => $selected['carrier_name'] ?? null,
            'shipping_name' => $selected['carrier_name'] ?? null,
            'shipping_cost' => (int) $selected['amount'],
            'amount' => (int) $selected['amount'],
            'currency' => $selected['currency'] ?? 'IDR',
            'estimated_days' => $selected['estimated_days'] ?? null,
        ]]);

        $state = $this->checkoutState->get($user);
        try {
            $plan = resolve(SuggestAllocation::class)->handle($cart, $address);
            $state['allocation_plan'] = $plan;
            $this->checkoutState->put($user, $state);
        } catch (Throwable $e) {
            report($e);
        }

        return $this->show($request);
    }

    public function placeOrder(Request $request): JsonResponse
    {
        $data = $request->validate([
            'payment_method_id' => ['required', 'integer'],
        ]);

        $user = $this->customer($request);
        $method = PaymentMethod::query()->where('is_enabled', true)->find($data['payment_method_id']);
        if (! $method) {
            return response()->json(['message' => 'Metode pembayaran tidak valid.'], 422);
        }

        $countryId = (int) (data_get($this->checkoutState->get($user), 'shipping_address.country_id')
            ?: $this->defaultCountry()?->countryId
            ?: 0);
        $options = $countryId > 0 ? resolve(FetchPaymentMethods::class)->handle($countryId) : [];
        $selected = collect($options)->first(fn (array $row): bool => (int) $row['id'] === (int) $method->id);
        if (! is_array($selected)) {
            return response()->json(['message' => 'Metode pembayaran tidak tersedia.'], 422);
        }

        if (($selected['driver'] ?? null) === 'stripe') {
            return response()->json(['message' => 'Stripe tidak dipakai di aplikasi pelanggan.'], 422);
        }

        $this->checkoutState->putPayment($user, $selected);
        $checkout = $this->checkoutState->get($user);
        $cart = $this->customerCart->current($user);

        if ($cart->lines->isEmpty()) {
            return response()->json(['message' => 'Keranjang kosong.'], 422);
        }

        try {
            $order = resolve(CreateOrder::class)->handle($checkout, $cart);
            resolve(NotifyOrderCustomer::class)->handle($order, OrderNotificationType::AwaitingPayment);

            $instructions = null;
            if (($selected['driver'] ?? null) === 'komerce') {
                $instructions = resolve(CreateKomercePayment::class)->handle($order, $selected);
            }

            $this->checkoutState->forget($user);

            return response()->json([
                'data' => [
                    'order_id' => $order->id,
                    'number' => $order->number,
                    'amount' => (int) $order->price_amount,
                    'currency' => $order->currency_code,
                    'payment_status' => $order->payment_status->value,
                    'payment' => $instructions,
                ],
            ], 201);
        } catch (Throwable $e) {
            report($e);

            return response()->json([
                'message' => $e->getMessage() !== '' ? $e->getMessage() : 'Gagal membuat pesanan.',
            ], 422);
        }
    }

    /**
     * @param  array<string, mixed>  $address
     * @return list<array<string, mixed>>
     */
    private function ratesForCart(Cart $cart, array $address): array
    {
        try {
            $plan = resolve(SuggestAllocation::class)->handle($cart, $address);
        } catch (Throwable) {
            return [];
        }

        if ($plan->shipments === []) {
            return [];
        }

        $packages = resolve(BuildShippingPackages::class)->handleFromLines($plan->shipments[0]->lines);

        return resolve(FetchDeliveryRates::class)->handle(
            $address,
            $packages,
            $plan->shipments[0]->inventory_id,
        );
    }

    private function normalizeDestinationId(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_scalar($value) ? trim((string) $value) : null;
    }

    private function customer(Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function defaultCountry(): ?CountryByZoneData
    {
        $countries = resolve(GetCountriesByZone::class)->handle();

        return $countries->firstWhere('countryCode', 'ID') ?? $countries->first();
    }
}
