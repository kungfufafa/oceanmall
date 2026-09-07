<?php

declare(strict_types=1);

namespace App\Livewire\Shopper\SlideOvers;

use Filament\Notifications\Notification;
use Shopper\Livewire\SlideOvers\ShipmentAddEvent as ShopperShipmentAddEvent;

final class ShipmentAddEvent extends ShopperShipmentAddEvent
{
    public function save(): void
    {
        if (komerce_shipping_delivery_enabled()) {
            $this->authorize('edit_orders');

            Notification::make()
                ->title('Status kirim hanya dari Komerce.')
                ->body('Jangan tambah event resi Shopper. Lacak AWB RajaOngkir.')
                ->danger()
                ->send();

            return;
        }

        parent::save();
    }
}
