<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OrderShipment;

/**
 * Request-scoped OrderShipment for Shopper's createShipment().
 *
 * Shopper's ShippingDriver::createShipment() only receives Address + Package +
 * service code. Komerce Store Order + Pickup needs the persisted shipment.
 */
final class KomerceFulfillmentContext
{
    private ?OrderShipment $shipment = null;

    public function set(OrderShipment $shipment): void
    {
        $this->shipment = $shipment;
    }

    public function shipment(): ?OrderShipment
    {
        return $this->shipment;
    }

    public function clear(): void
    {
        $this->shipment = null;
    }
}
