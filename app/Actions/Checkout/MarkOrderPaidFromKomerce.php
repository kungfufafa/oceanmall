<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Services\Komerce\PaymentClient;
use Illuminate\Support\Facades\DB;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;

final class MarkOrderPaidFromKomerce
{
    public function __construct(private readonly PaymentClient $payments) {}

    public function handle(string $paymentId): string
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

        if ($order->payment_status === PaymentStatus::Paid) {
            $this->dispatchPendingDeliveries($order);

            return 'already_processed';
        }

        $remotePayment = $this->payments->getStatus($paymentId);
        $remoteStatus = $this->remotePaymentStatus($remotePayment);

        if (strtoupper($remoteStatus) !== 'PAID') {
            return 'not_paid';
        }

        DB::transaction(function () use ($order, $transaction, $paymentId, $remotePayment, $remoteStatus): void {
            $order->refresh();

            if ($order->payment_status !== PaymentStatus::Paid) {
                $orderUpdates = ['payment_status' => PaymentStatus::Paid];

                if ($order->status === OrderStatus::New) {
                    $orderUpdates['status'] = OrderStatus::Processing;
                }

                $order->update($orderUpdates);
            }

            if ($transaction) {
                $transaction->refresh();
                $transaction->update([
                    'type' => TransactionType::Capture,
                    'status' => TransactionStatus::Success,
                ]);

                return;
            }

            PaymentTransaction::query()->create([
                'order_id' => $order->id,
                'payment_method_id' => $order->payment_method_id,
                'driver' => 'komerce',
                'type' => TransactionType::Capture,
                'status' => TransactionStatus::Success,
                'amount' => $this->remoteAmount($remotePayment) ?? (int) $order->price_amount,
                'currency_code' => $order->currency_code,
                'reference' => $paymentId,
                'metadata' => [
                    'komerce_payment_ref' => $paymentId,
                    'komerce_status' => $remoteStatus,
                ],
            ]);
        });

        $this->dispatchPendingDeliveries($order);

        return 'handled';
    }

    private function dispatchPendingDeliveries(Order $order): void
    {
        OrderShipment::query()
            ->where('order_id', $order->id)
            ->whereNull('awb')
            ->whereNull('tracking_number')
            ->pluck('id')
            ->each(static function (mixed $shipmentId): void {
                CreateRajaOngkirDeliveryForShipment::dispatch((int) $shipmentId);
            });
    }

    private function findOrderByPaymentReference(string $paymentId): ?Order
    {
        return Order::query()
            ->where('metadata->komerce_payment_ref', $paymentId)
            ->first();
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
    private function remoteAmount(array $response): ?int
    {
        $amount = data_get($response, 'data.amount') ?? data_get($response, 'amount');

        return is_numeric($amount) ? (int) $amount : null;
    }
}
