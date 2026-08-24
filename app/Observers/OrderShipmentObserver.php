<?php

declare(strict_types=1);

namespace App\Observers;

use App\Actions\Shipping\DispatchRajaOngkirDelivery;
use App\Models\OrderShipment;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class OrderShipmentObserver
{
    public function created(OrderShipment $shipment): void
    {
        if (! komerce_shipping_delivery_enabled() || app()->runningUnitTests() || $shipment->awb || $shipment->tracking_number) {
            return;
        }

        $order = $shipment->order;
        if (! $order instanceof Order) {
            return;
        }

        $paid = $order->payment_status === PaymentStatus::Paid;
        if ($paid) {
            resolve(DispatchRajaOngkirDelivery::class)->dispatchOne((int) $shipment->id);
        }
    }
}
