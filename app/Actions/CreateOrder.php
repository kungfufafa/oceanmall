<?php

declare(strict_types=1);

namespace App\Actions;

use App\Actions\Warehouse\SuggestAllocation;
use App\CheckoutSession;
use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use App\Models\OrderShipment;
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
                $order = resolve(CreateOrderFromCartAction::class)->execute($cart);

                $shipmentTotal = $this->storeShipments($order, $allocationPlan, $checkout);
                $shippingPrice = $shipmentTotal ?? (int) data_get($checkout, 'shipping_option.0.price', 0);

                $order->update([
                    'shipping_option_id' => $this->shippingOptionId($checkout),
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

    private function shippingOptionId(mixed $checkout): ?int
    {
        $id = data_get($checkout, 'shipping_option.0.id');

        if (is_int($id)) {
            return $id;
        }

        return is_string($id) && ctype_digit($id) ? (int) $id : null;
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
