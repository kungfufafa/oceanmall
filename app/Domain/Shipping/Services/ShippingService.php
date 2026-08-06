<?php

declare(strict_types=1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Contracts\ShippingDriverContract;
use App\Domain\Shipping\DTO\DeliveryOrderRequestData;
use App\Domain\Shipping\DTO\DeliveryOrderResultData;
use App\Domain\Shipping\DTO\ShippingRateRequestData;
use App\Domain\Shipping\DTO\ShippingTrackingData;
use Illuminate\Support\Collection;

class ShippingService
{
    public function __construct(
        protected ShippingDriverContract $driver,
    ) {}

    public function getDeliveryRates(ShippingRateRequestData $request): Collection
    {
        return $this->driver->calculateRates($request);
    }

    public function createDeliveryOrder(DeliveryOrderRequestData $request): DeliveryOrderResultData
    {
        return $this->driver->createDeliveryOrder($request);
    }

    public function trackWaybill(string $waybill, string $courier): ShippingTrackingData
    {
        return $this->driver->trackShipment($waybill, $courier);
    }
}
