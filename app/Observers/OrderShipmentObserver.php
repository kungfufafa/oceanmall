<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class OrderShipmentObserver
{
    public function created(OrderShipment $shipment): void
    {
        if (! komerce_enabled() || $shipment->awb || $shipment->tracking_number) {
            return;
        }

        $order = $shipment->order;
        if (! $order instanceof Order) {
            return;
        }

        $paid = $order->payment_status === PaymentStatus::Paid;
        $processing = $order->status === OrderStatus::Processing;

        if ($paid || $processing) {
            CreateRajaOngkirDeliveryForShipment::dispatch((int) $shipment->id);
        }
    }
}
