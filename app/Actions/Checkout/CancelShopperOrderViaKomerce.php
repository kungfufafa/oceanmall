<?php

declare(strict_types=1);

namespace App\Actions\Checkout;

use App\Actions\Notify\NotifyOrderCustomer;
use App\Actions\Shipping\CancelKomerceDelivery;
use App\Actions\Stock\ReleaseOrderShipmentStock;
use App\Enums\OrderNotificationType;
use App\Models\OrderShipment;
use Illuminate\Support\Facades\DB;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Events\Orders\OrderCancelled;
use Shopper\Core\Models\Order;

/**
 * Shopper cancel is a store-framework action. Komerce remains source of
 * truth for payment/delivery; this bridge cancels both sides together.
 */
final readonly class CancelShopperOrderViaKomerce
{
    public function __construct(
        private CancelUnpaidKomerceOrder $cancelUnpaid,
        private ReleaseOrderShipmentStock $releaseStock,
        private NotifyOrderCustomer $notifyOrderCustomer,
    ) {}

    public function handle(Order $order): Order
    {
        if ($order->status === OrderStatus::Cancelled) {
            event(new OrderCancelled($order));

            return $order;
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            return $this->cancelUnpaid->handle($order);
        }

        return DB::transaction(function () use ($order): Order {
            $order = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($order->status === OrderStatus::Cancelled) {
                return $order;
            }

            $order->forceFill([
                'status' => OrderStatus::Cancelled,
                'cancelled_at' => now(),
            ])->save();

            $hasAirwayBill = OrderShipment::query()
                ->where('order_id', $order->id)
                ->where(function ($query): void {
                    $query->where(function ($awb): void {
                        $awb->whereNotNull('awb')->where('awb', '!=', '');
                    })->orWhere(function ($tracking): void {
                        $tracking->whereNotNull('tracking_number')->where('tracking_number', '!=', '');
                    });
                })
                ->exists();

            if (! $hasAirwayBill) {
                $this->releaseStock->handle($order->refresh());
            }

            $order = $order->refresh();
            $this->notifyOrderCustomer->handle($order, OrderNotificationType::Cancelled);
            resolve(CancelKomerceDelivery::class)->handle($order);
            event(new OrderCancelled($order));

            return $order;
        });
    }
}
