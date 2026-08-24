<?php

declare(strict_types=1);

namespace App\Livewire\Shopper\SlideOvers;

use App\Actions\Shipping\IssueRajaOngkirFulfillment;
use App\Models\OrderShipment;
use Filament\Notifications\Notification;
use RuntimeException;
use Shopper\Core\Models\Carrier;
use Shopper\Livewire\SlideOvers\CreateShippingLabel as BaseCreateShippingLabel;
use Throwable;

final class CreateShippingLabel extends BaseCreateShippingLabel
{
    public function mount(): void
    {
        parent::mount();

        $shipment = OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->first();

        $carrierId = $this->order->shippingOption?->carrier_id;

        if (! $carrierId && $shipment) {
            $carrierCode = strtolower((string) ($shipment->carrier_code ?: $shipment->carrier_name));
            $carrierId = Carrier::query()
                ->where('slug', $carrierCode)
                ->orWhere('name', 'like', "%{$carrierCode}%")
                ->value('id');
        }

        if (! $carrierId) {
            $carrierId = Carrier::query()->where('is_enabled', true)->value('id');
        }

        $awb = $shipment?->awb ?? $shipment?->tracking_number;

        $state = $this->form->getState();
        $state['carrier_id'] = $carrierId;
        if ($awb !== null && $awb !== '') {
            $state['tracking_number'] = $awb;
            $state['tracking_url'] = route('account.orders.show', $this->order);
        }

        $this->form->fill($state);
    }

    public function save(): void
    {
        if (! komerce_shipping_delivery_enabled()) {
            parent::save();

            return;
        }

        $this->authorize('edit_orders');

        try {
            $result = resolve(IssueRajaOngkirFulfillment::class)->handle($this->order);
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('RajaOngkir belum bisa menerbitkan resi.')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal menerbitkan AWB RajaOngkir.')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Resi RajaOngkir tercatat di Shopper.')
            ->body('AWB '.implode(', ', $result['awbs']).'. Shopper tidak membuat nomor resi sendiri.')
            ->success()
            ->send();

        $this->dispatch('order.shipping.created');
        $this->closePanel();
        $this->redirect($result['print_route']);
    }
}
