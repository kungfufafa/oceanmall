<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Notify\NotifyOrderCustomer;
use App\Enums\OrderNotificationType;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\QrislyClient;
use Illuminate\Support\Facades\DB;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;

final class MarkOrderPaidFromKomerce
{
    public function __construct(
        private readonly PaymentClient $payments,
        private readonly QrislyClient $qrisly,
        private readonly NotifyOrderCustomer $notifyOrderCustomer,
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

        if ($provider === 'qrisly') {
            if (! qrisly_enabled()) {
                return 'not_paid';
            }

            $remotePayment = $this->qrisly->getPaymentStatus($paymentId);
            $remoteStatus = $this->remoteQrislyStatus($remotePayment);

            if (! $this->isPaidStatus($remoteStatus)) {
                return 'not_paid';
            }
        } else {
            $remotePayment = $this->payments->getStatus($paymentId);
            $remoteStatus = $this->remotePaymentStatus($remotePayment);

            if (! $this->isPaidStatus($remoteStatus)) {
                return 'not_paid';
            }
        }

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
        if (! komerce_shipping_delivery_enabled()) {
            return;
        }

        OrderShipment::query()
            ->where('order_id', $order->id)
            ->whereNull('awb')
            ->whereNull('tracking_number')
            ->pluck('id')
            ->each(static function (mixed $shipmentId): void {
                CreateRajaOngkirDeliveryForShipment::dispatch((int) $shipmentId);
            });
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
     * @param  array<string, mixed>  $response
     */
    private function remotePaymentStatus(array $response): string
    {
        return (string) (data_get($response, 'data.status') ?? data_get($response, 'status', ''));
    }

    /**
     * @param  array<string, mixed>  $response
     */
    private function remoteQrislyStatus(array $response): string
    {
        return (string) (
            data_get($response, 'data.payment_status')
            ?? data_get($response, 'payment_status')
            ?? data_get($response, 'data.status')
            ?? data_get($response, 'status', '')
        );
    }

    private function isPaidStatus(string $status): bool
    {
        return strtoupper(trim($status)) === 'PAID';
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
