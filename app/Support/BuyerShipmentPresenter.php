<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\OrderShipment;

/**
 * Buyer-facing shipment payload for Vue account + API v1 / Expo.
 */
final class BuyerShipmentPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function payload(OrderShipment $shipment): array
    {
        return [
            'id' => $shipment->id,
            'inventory_name' => $shipment->inventory?->name,
            'status' => $shipment->status,
            'status_label' => ShipmentStatusLabel::for(
                is_string($shipment->status) ? $shipment->status : null,
            ),
            'awb' => $shipment->awb,
            'tracking_number' => $shipment->tracking_number,
            'carrier' => $shipment->carrier_name ?? $shipment->carrier_code,
            'service' => $shipment->service_name ?? $shipment->service_code,
            'carrier_logo' => KomerceCourierAssets::logoUrl($shipment->carrier_code),
            'cost' => $shipment->cost,
            'currency' => $shipment->currency_code,
            'tracking_history' => ShipmentTrackingHistory::fromShipment($shipment),
        ];
    }
}
