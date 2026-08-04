<?php

declare(strict_types=1);

namespace App\Actions\Account;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Actions\Shipping\SyncOrderShippingFromShipments;
use App\Models\OrderShipment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final readonly class ConfirmOrderReceived
{
    public function __construct(private SyncOrderShippingFromShipments $syncOrderShipping) {}

    public function handle(Order $order): Order
    {
        if ($order->status === OrderStatus::Completed && $order->shipping_status?->value === 'delivered') {
            return $order;
        }

        if ($order->status === OrderStatus::Cancelled) {
            throw ValidationException::withMessages([
                'received' => __('This order was cancelled and cannot be confirmed as received.'),
            ]);
        }

        if ($order->payment_status !== PaymentStatus::Paid) {
            throw ValidationException::withMessages([
                'received' => __('Payment must be completed before confirming delivery.'),
            ]);
        }

        $shipments = OrderShipment::query()->where('order_id', $order->id)->get();

        if ($shipments->isEmpty()) {
            throw ValidationException::withMessages([
                'received' => __('This order has no shipments to confirm.'),
            ]);
        }

        $hasAirwayBill = $shipments->contains(function (OrderShipment $shipment): bool {
            return (is_string($shipment->awb) && trim($shipment->awb) !== '')
                || (is_string($shipment->tracking_number) && trim($shipment->tracking_number) !== '');
        });

        if (! $hasAirwayBill) {
            throw ValidationException::withMessages([
                'received' => __('Shipments must have an airway bill before you can confirm receipt.'),
            ]);
        }

        return DB::transaction(function () use ($order, $shipments): Order {
            foreach ($shipments as $shipment) {
                $shipment->forceFill(['status' => NormalizeShipmentStatus::DELIVERED])->save();
            }

            $order->forceFill(['status' => OrderStatus::Completed])->save();

            return $this->syncOrderShipping->handle($order->refresh());
        });
    }
}
