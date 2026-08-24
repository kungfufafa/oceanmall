<?php

declare(strict_types=1);

namespace App\Livewire\Shopper\Pages;

use App\Actions\Shipping\RefreshShipmentTracking;
use App\Models\OrderShipment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Width;
use Filament\Tables\Table;
use Mckenziearts\Icons\Untitledui\Enums\Untitledui;
use Shopper\Core\Actions\MarkShipmentDeliveredAction;
use Shopper\Core\Models\OrderShipping;
use Shopper\Livewire\Pages\Order\Shipments as ShopperShipments;
use Throwable;

final class OrderShipments extends ShopperShipments
{
    public function table(Table $table): Table
    {
        $table = parent::table($table);

        if (! komerce_shipping_delivery_enabled()) {
            return $table;
        }

        return $table->recordActions([
            Action::make('printRajaOngkirLabel')
                ->label('Cetak Stiker RajaOngkir')
                ->icon(Untitledui::Printer)
                ->color('primary')
                ->authorize('edit_orders')
                ->visible(fn (OrderShipping $record): bool => $this->orderShipmentFor($record) !== null)
                ->url(function (OrderShipping $record): ?string {
                    $shipment = $this->orderShipmentFor($record);

                    if ($shipment === null || $shipment->order === null) {
                        return null;
                    }

                    return route('shopper.orders.fulfillment.print-label', [
                        'order' => $shipment->order,
                        'shipment' => $shipment->id,
                    ]);
                }, shouldOpenInNewTab: true),
            Action::make('refreshRajaOngkirTracking')
                ->label('Perbarui pelacakan RajaOngkir')
                ->icon(Untitledui::RefreshCcw02)
                ->color('gray')
                ->authorize('edit_orders')
                ->visible(fn (OrderShipping $record): bool => filled($record->tracking_number))
                ->action(function (OrderShipping $record): void {
                    $shipment = $this->orderShipmentFor($record);

                    if ($shipment === null) {
                        Notification::make()
                            ->title('Pengiriman RajaOngkir tidak ditemukan.')
                            ->danger()
                            ->send();

                        return;
                    }

                    try {
                        resolve(RefreshShipmentTracking::class)->handle($shipment);
                    } catch (Throwable $e) {
                        report($e);

                        Notification::make()
                            ->title('Gagal memperbarui pelacakan RajaOngkir.')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Status kurir diperbarui dari RajaOngkir.')
                        ->success()
                        ->send();
                }),
            Action::make('markDelivered')
                ->label(__('shopper::forms.actions.mark_delivered'))
                ->icon(Untitledui::PackageCheck)
                ->color('success')
                ->authorize('edit_orders')
                ->visible(false)
                ->action(function (OrderShipping $record): void {
                    (new MarkShipmentDeliveredAction)->execute($record);
                }),
            Action::make('edit')
                ->label(__('shopper::forms.actions.edit'))
                ->icon(Untitledui::Edit03)
                ->iconButton()
                ->authorize('edit_orders')
                ->visible(false)
                ->modalWidth(Width::Large)
                ->action(fn (): null => null),
            Action::make('view')
                ->label(__('shopper::pages/orders.shipment.manage'))
                ->icon(Untitledui::Sliders)
                ->iconButton()
                ->tooltip(__('shopper::pages/orders.shipment.manage'))
                ->action(fn (OrderShipping $record) => $this->dispatch(
                    'openPanel',
                    component: 'shopper-slide-overs.shipment-detail',
                    arguments: ['shipment' => $record],
                )),
        ]);
    }

    private function orderShipmentFor(OrderShipping $record): ?OrderShipment
    {
        $awb = trim((string) $record->tracking_number);

        if ($awb === '') {
            return OrderShipment::query()
                ->where('order_id', $record->order_id)
                ->orderByDesc('id')
                ->first();
        }

        return OrderShipment::query()
            ->where('order_id', $record->order_id)
            ->where(function ($query) use ($awb): void {
                $query->where('awb', $awb)->orWhere('tracking_number', $awb);
            })
            ->orderByDesc('id')
            ->first();
    }
}
