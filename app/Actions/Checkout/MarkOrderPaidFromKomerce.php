<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Notify\NotifyOrderCustomer;
use App\Actions\Shipping\DispatchRajaOngkirDelivery;
use App\Enums\OrderNotificationType;
use App\Support\KomercePaymentLookupContext;
use Illuminate\Support\Facades\DB;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Facades\Payment;
use Shopper\Payment\Models\PaymentTransaction;
use Throwable;

final class MarkOrderPaidFromKomerce
{
    public function __construct(
        private readonly NotifyOrderCustomer $notifyOrderCustomer,
        private readonly KomercePaymentLookupContext $lookupContext,
    ) {}

    /**
     * @param  'payment_api'|'qrisly'|null  $provider  Null = auto-detect from transaction/order metadata.
     */
    public function handle(string $paymentId, ?string $provider = null): string
    {
        $transaction = PaymentTransaction::query()
            ->where('driver', 'komerce')
            ->where('reference', $paymentId)
            ->first();

        $order = $transaction
            ? Order::query()->find($transaction->order_id)
            : $this->findOrderByPaymentReference($paymentId);

        if (! $transaction && ! $order) {
            return 'no_transaction';
        }

        if (! $order) {
            return 'no_order';
        }

        if ($this->isTerminallyCancelled($order)) {
            return 'cancelled_order';
        }

        if ($order->payment_status === PaymentStatus::Paid) {
            $this->dispatchPendingDeliveries($order);

            return 'already_processed';
        }

        $provider ??= $this->resolveProvider($transaction, $order);

        $this->lookupContext->setProvider($provider);

        try {
            $retrieved = Payment::driver('komerce')->retrievePayment($paymentId);
        } catch (Throwable) {
            return 'not_paid';
        } finally {
            $this->lookupContext->clear();
        }

        if ($retrieved->status !== 'captured') {
            return 'not_paid';
        }

        $remotePayment = is_array($retrieved->data['raw_response'] ?? null)
            ? $retrieved->data['raw_response']
            : [];
        $remoteStatus = 'PAID';

        $result = DB::transaction(function () use ($order, $transaction, $paymentId, $remotePayment, $remoteStatus, $provider): array {
            $lockedOrder = Order::query()->lockForUpdate()->find($order->id);

            if (! $lockedOrder instanceof Order) {
                return ['status' => 'no_order', 'order' => null, 'newly_paid' => false];
            }

            if ($this->isTerminallyCancelled($lockedOrder)) {
                return ['status' => 'cancelled_order', 'order' => $lockedOrder, 'newly_paid' => false];
            }

            if ($lockedOrder->payment_status === PaymentStatus::Paid) {
                return ['status' => 'already_processed', 'order' => $lockedOrder, 'newly_paid' => false];
            }

            $lockedTransaction = $transaction instanceof PaymentTransaction
                ? PaymentTransaction::query()->lockForUpdate()->find($transaction->id)
                : null;

            if ($this->amountMismatches($lockedOrder, $lockedTransaction, $remotePayment, $provider)) {
                return ['status' => 'amount_mismatch', 'order' => $lockedOrder, 'newly_paid' => false];
            }

            $orderUpdates = ['payment_status' => PaymentStatus::Paid];

            if ($lockedOrder->status === OrderStatus::New) {
                $orderUpdates['status'] = OrderStatus::Processing;
            }

            $lockedOrder->update($orderUpdates);

            if ($lockedTransaction instanceof PaymentTransaction) {
                $lockedTransaction->update([
                    'type' => TransactionType::Capture,
                    'status' => TransactionStatus::Success,
                ]);
            } else {
                PaymentTransaction::query()->create([
                    'order_id' => $lockedOrder->id,
                    'payment_method_id' => $lockedOrder->payment_method_id,
                    'driver' => 'komerce',
                    'type' => TransactionType::Capture,
                    'status' => TransactionStatus::Success,
                    'amount' => $this->remoteAmount($remotePayment, $provider) ?? (int) $lockedOrder->price_amount,
                    'currency_code' => $lockedOrder->currency_code,
                    'reference' => $paymentId,
                    'metadata' => [
                        'komerce_payment_ref' => $paymentId,
                        'komerce_provider' => $provider,
                        'komerce_status' => $remoteStatus,
                    ],
                ]);
            }

            return ['status' => 'handled', 'order' => $lockedOrder, 'newly_paid' => true];
        });

        if ($result['status'] === 'amount_mismatch') {
            report(new \RuntimeException(sprintf(
                'Komerce payment amount mismatch for order %s (payment %s).',
                $order->number,
                $paymentId,
            )));

            return 'amount_mismatch';
        }

        if ($result['status'] === 'cancelled_order' || $result['status'] === 'no_order') {
            return $result['status'];
        }

        /** @var Order $processedOrder */
        $processedOrder = $result['order'];

        if ($result['newly_paid']) {
            $this->notifyOrderCustomer->handle($processedOrder->refresh(), OrderNotificationType::Paid);
        }

        $this->dispatchPendingDeliveries($processedOrder);

        return $result['status'];
    }

    private function dispatchPendingDeliveries(Order $order): void
    {
        resolve(DispatchRajaOngkirDelivery::class)->handle($order);
    }

    private function isTerminallyCancelled(Order $order): bool
    {
        return $order->status === OrderStatus::Cancelled
            || in_array($order->payment_status, [
                PaymentStatus::Voided,
                PaymentStatus::Refunded,
            ], true);
    }

    private function findOrderByPaymentReference(string $paymentId): ?Order
    {
        return Order::query()
            ->where(function ($query) use ($paymentId): void {
                $query->where('metadata->komerce_payment_ref', $paymentId)
                    ->orWhere('metadata->komerce->payment_ref', $paymentId)
                    ->orWhere('metadata->komerce->qrisly_history_id', $paymentId);
            })
            ->first();
    }

    private function resolveProvider(?PaymentTransaction $transaction, Order $order): string
    {
        $fromTx = data_get($transaction?->metadata, 'komerce_provider');
        if (is_string($fromTx) && $fromTx !== '') {
            return $fromTx;
        }

        $metadata = $order->getAttribute('metadata');
        if (is_string($metadata) && $metadata !== '') {
            $decoded = json_decode($metadata, true);
            $metadata = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($metadata)) {
            $metadata = [];
        }

        $fromOrder = data_get($metadata, 'komerce.provider');

        return is_string($fromOrder) && $fromOrder !== '' ? $fromOrder : 'payment_api';
    }

    /**
     * Reject paid callbacks whose remote amount is far from the order total.
     * Allows up to 999 IDR drift for QRISLY unique-amount suffixes.
     *
     * @param  array<string, mixed>  $remotePayment
     */
    private function amountMismatches(
        Order $order,
        ?PaymentTransaction $transaction,
        array $remotePayment,
        string $provider,
    ): bool {
        $remoteAmount = $this->remoteAmount($remotePayment, $provider);

        if ($remoteAmount === null) {
            return false;
        }

        $expected = (int) ($transaction?->amount ?? $order->price_amount);
        $tolerance = $provider === 'qrisly' ? 999 : 0;

        return abs($remoteAmount - $expected) > $tolerance;
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function remoteAmount(array $response, string $provider): ?int
    {
        $paths = $provider === 'qrisly'
            ? ['data.final_amount', 'data.original_amount', 'data.amount', 'final_amount', 'amount', 'paid_amount']
            : ['data.amount', 'amount'];

        foreach ($paths as $path) {
            $amount = data_get($response, $path);
            if (is_numeric($amount)) {
                return (int) $amount;
            }
        }

        return null;
    }
}
