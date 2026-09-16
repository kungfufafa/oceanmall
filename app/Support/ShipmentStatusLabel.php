<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Buyer-safe shipment status wording shared by Shopper, Vue, and API/Expo.
 *
 * Keep this identical to OrderShipmentOpsPresenter::statusLabel().
 * Do not invent a second vocabulary.
 */
final class ShipmentStatusLabel
{
    public static function for(?string $status): string
    {
        return match ($status) {
            'pending', 'ready' => 'Menunggu resi',
            'labeled' => 'Resi terbit',
            'picked_up' => 'Sudah dijemput',
            'in_transit' => 'Dalam pengiriman',
            'delivered' => 'Terkirim',
            default => $status ? str_replace('_', ' ', ucfirst($status)) : 'Unknown',
        };
    }
}
