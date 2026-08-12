<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Notify\NotifyOrderCustomer;
use App\Actions\Stock\ReleaseOrderShipmentStock;
use App\Enums\OrderNotificationType;
use App\Services\Komerce\PaymentClient;
use Illuminate\Support\Facades\DB;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Models\PaymentTransaction;
use Throwable;

final readonly class CancelUnpaidKomerceOrder
{
    public function __construct(
        private PaymentClient $payments,
        private ReleaseOrderShipmentStock $releaseStock,
        private NotifyOrderCustomer $notifyOrderCustomer,
    ) {}

    public function handle(Order $order, string $reason = 'Payment expired'): Order
    {
        if ($order->payment_status === PaymentStatus::Paid) {
            return $order;
        }

        if ($order->status === OrderStatus::Cancelled) {
            return $order;
        }

        return DB::transaction(function () use ($order, $reason): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->payment_status === PaymentStatus::Paid || $order->status === OrderStatus::Cancelled) {
                return $order;
            }

            $this->cancelRemotePayment($order, $reason);

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'payment_status' => PaymentStatus::Voided,
            ])->save();

            PaymentTransaction::query()
                ->where('order_id', $order->id)
                ->where('driver', 'komerce')
                ->where('status', TransactionStatus::Pending)
                ->update(['status' => TransactionStatus::Failed]);

            $this->releaseStock->handle($order->refresh());

            $order = $order->refresh();
            $this->notifyOrderCustomer->handle($order, OrderNotificationType::Cancelled);

            return $order;
        });
    }

    private function cancelRemotePayment(Order $order, string $reason): void
    {
        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $provider = data_get($metadata, 'komerce.provider', 'payment_api');

        // QRISLY has no documented cancel endpoint — expire locally only.
        if ($provider === 'qrisly') {
            return;
        }

        $paymentId = data_get($metadata, 'komerce.payment_ref')
            ?? PaymentTransaction::query()
                ->where('order_id', $order->id)
                ->where('driver', 'komerce')
                ->value('reference');

        if (! is_string($paymentId) || trim($paymentId) === '') {
            return;
        }

        if (! komerce_payment_enabled()) {
            return;
        }

        try {
            $this->payments->cancel(trim($paymentId), $reason);
        } catch (Throwable $e) {
            report($e);
        }
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
}
