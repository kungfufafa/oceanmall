<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\DTO\DeliveryOrderRequestData;
use App\Domain\Shipping\DTO\DeliveryOrderResultData;
use App\Domain\Shipping\DTO\ShippingRateRequestData;
use App\Domain\Shipping\DTO\ShippingTrackingData;
use Illuminate\Support\Collection;

interface ShippingDriverContract
{
    /**
     * Calculate courier shipping rates for package origin & destination.
     *
     * @return Collection<int, \App\Domain\Shipping\DTO\ShippingRateData>
     */
    public function calculateRates(ShippingRateRequestData $request): Collection;

    /**
     * Create delivery order with courier and obtain AWB/Waybill number.
     */
    public function createDeliveryOrder(DeliveryOrderRequestData $request): DeliveryOrderResultData;

    /**
     * Track package shipment status by waybill number and courier.
     */
    public function trackShipment(string $waybill, string $courier): ShippingTrackingData;
}
