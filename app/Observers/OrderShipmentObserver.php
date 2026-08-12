<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
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
            CreateRajaOngkirDeliveryForShipment::dispatch((int) $shipment->id);
        }
    }
}
