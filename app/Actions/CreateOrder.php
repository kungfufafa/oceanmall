<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Warehouse\SuggestAllocation;
use App\CheckoutSession;
use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use App\Models\OrderShipment;
use App\Support\CheckoutAllocationContext;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Shopper\Cart\Actions\CreateOrderFromCartAction;
use Shopper\Cart\Models\Cart;
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
                $allocationPlan = $this->resolveAllocationPlan($cart, $checkout);

                $context = resolve(CheckoutAllocationContext::class);
                $context->set($allocationPlan);

                try {
                    $order = resolve(CreateOrderFromCartAction::class)->execute($cart);
                } finally {
                    $context->clear();
                }

                $shipmentTotal = $this->storeShipments($order, $allocationPlan, $checkout);
                $shippingPrice = $shipmentTotal ?? (int) data_get($checkout, 'shipping_option.0.price', 0);

                $order->update([
                    'customer_id' => $order->customer_id ?? Auth::id() ?? $cart->customer_id,
                    'shipping_option_id' => $this->shippingOptionId($checkout),
                    'payment_method_id' => data_get($checkout, 'payment.0.id'),
                    'price_amount' => $order->price_amount + $shippingPrice,
                ]);

                $this->storeShippingAddressMetadata($order, $checkout);
                $this->storePaymentMetadata($order, $checkout);
                $this->storeKomercePaymentReference($order, $checkout);

                return $order;
            });
        } finally {
            $lock->release();
        }
    }

    private function shippingOptionId(mixed $checkout): ?int
    {
        $id = data_get($checkout, 'shipping_option.0.id');
        if (is_int($id)) {
            return $id;
        }

        $carrierCode = data_get($checkout, 'shipping_option.0.carrier_code');
        if (is_string($carrierCode) && $carrierCode !== '') {
            $carrierId = \Shopper\Core\Models\Carrier::query()
                ->where('slug', $carrierCode)
                ->value('id');
            
            if ($carrierId) {
                return (int) $carrierId;
            }
        }

        return is_string($id) && ctype_digit($id) ? (int) $id : null;
    }

    private function storeShippingAddressMetadata(Order $order, mixed $checkout): void
    {
        $shippingAddress = (array) data_get($checkout, 'shipping_address', []);
        $metadataAddress = [];

        $countryId = data_get($shippingAddress, 'country_id');
        if (is_int($countryId) || (is_string($countryId) && ctype_digit($countryId))) {
            $metadataAddress['country_id'] = (int) $countryId;
        }

        $destinationId = data_get($shippingAddress, 'rajaongkir_destination_id')
            ?? data_get($shippingAddress, 'destination_id');

        if (is_scalar($destinationId) && trim((string) $destinationId) !== '') {
            $metadataAddress['rajaongkir_destination_id'] = trim((string) $destinationId);
        }

        // Persist full address fields so delivery jobs can populate receiver_name /
        // receiver_address even when the sh_order_addresses row has null columns.
        foreach (['first_name', 'last_name', 'street_address', 'street_address_plus', 'postal_code', 'city', 'state', 'phone_number'] as $field) {
            $value = data_get($shippingAddress, $field);
            if (is_scalar($value) && trim((string) $value) !== '') {
                $metadataAddress[$field] = trim((string) $value);
            }
        }

        if ($metadataAddress === []) {
            return;
        }

        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $existingAddress = data_get($metadata, 'shipping_address');

        $metadata['shipping_address'] = array_merge(
            is_array($existingAddress) ? $existingAddress : [],
            $metadataAddress,
        );

        $order->forceFill([
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ])->save();
    }

    private function storePaymentMetadata(Order $order, mixed $checkout): void
    {
        $selectedPayment = (array) data_get($checkout, 'payment.0', []);
        $channelCode = data_get($selectedPayment, 'channel_code');
        $paymentType = data_get($selectedPayment, 'payment_type');

        if (! $channelCode && ! $paymentType) {
            return;
        }

        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $komerceMeta = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];

        if ($channelCode) {
            $komerceMeta['channel_code'] = (string) $channelCode;
        }
        if ($paymentType) {
            $komerceMeta['payment_type'] = (string) $paymentType;
        }

        $metadata['komerce'] = $komerceMeta;

        $order->forceFill([
            'metadata' => json_encode($metadata, JSON_THROW_ON_ERROR),
        ])->save();
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function resolveAllocationPlan(Cart $cart, mixed $checkout): AllocationPlan
    {
        $storedPlan = data_get($checkout, 'allocation_plan')
            ?? data_get($checkout, 'allocation');

        if ($storedPlan instanceof AllocationPlan) {
            return $storedPlan;
        }

        if (is_array($storedPlan)) {
            return $this->allocationPlanFromArray($storedPlan);
        }

        return resolve(SuggestAllocation::class)->handle(
            $cart,
            (array) data_get($checkout, 'shipping_address', []),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function allocationPlanFromArray(array $payload): AllocationPlan
    {
        $shipments = data_get($payload, 'shipments', $payload);

        return new AllocationPlan(
            collect($shipments)
                ->map(fn (mixed $shipment): ShipmentDraft => $this->shipmentDraftFromArray((array) $shipment))
                ->values()
                ->all(),
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function shipmentDraftFromArray(array $payload): ShipmentDraft
    {
        return new ShipmentDraft(
            (int) data_get($payload, 'inventory_id'),
            collect(data_get($payload, 'lines', []))
                ->map(static fn (mixed $line): array => [
                    'purchasable_type' => (string) data_get($line, 'purchasable_type'),
                    'purchasable_id' => (int) data_get($line, 'purchasable_id'),
                    'qty' => (int) (data_get($line, 'qty') ?? data_get($line, 'quantity', 0)),
                ])
                ->values()
                ->all(),
        );
    }

    private function storeShipments(Order $order, AllocationPlan $allocationPlan, mixed $checkout): ?int
    {
        if ($allocationPlan->shipments === []) {
            return null;
        }

        $shipmentCount = count($allocationPlan->shipments);
        $total = 0;

        foreach ($allocationPlan->shipments as $shipmentDraft) {
            $rate = $this->rateForShipment($shipmentDraft, $checkout, $shipmentCount);
            $cost = $this->rateCost($rate);
            $total += $cost;

            $shipment = OrderShipment::query()->create([
                'order_id' => $order->id,
                'inventory_id' => $shipmentDraft->inventory_id,
                'carrier_code' => $this->rateString($rate, 'carrier_code'),
                'carrier_name' => $this->rateString($rate, 'carrier_name')
                    ?? $this->rateString($rate, 'name'),
                'service_code' => $this->rateString($rate, 'service_code')
                    ?? $this->rateString($rate, 'id'),
                'service_name' => $this->rateString($rate, 'service_name')
                    ?? $this->rateString($rate, 'description'),
                'cost' => $cost,
                'currency_code' => $this->rateString($rate, 'currency_code')
                    ?? $this->rateString($rate, 'currency')
                    ?? $order->currency_code,
                'status' => 'pending',
                'metadata' => $rate === [] ? null : ['rate' => $rate],
            ]);

            foreach ($shipmentDraft->lines as $line) {
                $shipment->lines()->create([
                    'purchasable_type' => $line['purchasable_type'],
                    'purchasable_id' => $line['purchasable_id'],
                    'qty' => $line['qty'],
                ]);
            }
        }

        return $total;
    }

    /**
     * @return array<string, mixed>
     */
    private function rateForShipment(ShipmentDraft $shipmentDraft, mixed $checkout, int $shipmentCount): array
    {
        $ratesByShipment = data_get($checkout, 'shipping_options_by_shipment', []);

        if (is_array($ratesByShipment)) {
            foreach ([
                $shipmentDraft->inventory_id,
                (string) $shipmentDraft->inventory_id,
            ] as $key) {
                if (array_key_exists($key, $ratesByShipment) && is_array($ratesByShipment[$key])) {
                    return $ratesByShipment[$key];
                }
            }

            foreach ($ratesByShipment as $rate) {
                if (
                    is_array($rate)
                    && (int) data_get($rate, 'inventory_id') === $shipmentDraft->inventory_id
                ) {
                    return $rate;
                }
            }
        }

        $globalRate = data_get($checkout, 'shipping_option.0');

        return $shipmentCount === 1 && is_array($globalRate) ? $globalRate : [];
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    private function rateCost(array $rate): int
    {
        return (int) (data_get($rate, 'amount')
            ?? data_get($rate, 'price')
            ?? data_get($rate, 'cost')
            ?? 0);
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    private function rateString(array $rate, string $key): ?string
    {
        $value = data_get($rate, $key);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
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
