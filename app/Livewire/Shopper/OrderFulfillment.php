<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use App\Actions\Shipping\IssueRajaOngkirFulfillment;
use App\Models\OrderShipment;
use Filament\Notifications\Notification;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use RuntimeException;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Livewire\Components\Orders\Fulfillment as ShopperFulfillment;
use Throwable;

final class OrderFulfillment extends ShopperFulfillment
{
    public function openShippingLabel(): void
    {
        if (! komerce_shipping_delivery_enabled()) {
            parent::openShippingLabel();

            return;
        }

        Gate::authorize('print-shipment-label', $this->order);

        try {
            $result = resolve(IssueRajaOngkirFulfillment::class)->handle($this->order);
        } catch (RuntimeException $e) {
            Notification::make()
                ->title('Resi RajaOngkir belum terbit.')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->title('Gagal menerbitkan resi RajaOngkir.')
                ->body($e->getMessage())
                ->danger()
                ->send();

            return;
        }

        $this->order->refresh();
        $this->dispatch('order.shipping.created');
        $this->dispatch('order.updated');

        Notification::make()
            ->title('Resi RajaOngkir tercatat di Shopper.')
            ->body('AWB '.implode(', ', $result['awbs']).'. Shopper tidak membuat nomor resi sendiri.')
            ->success()
            ->send();

        $this->redirect($result['print_route']);
    }

    public function render(): View
    {
        $view = parent::render();

        $shipments = OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->orderBy('id')
            ->get();

        $awbs = $shipments
            ->map(static fn (OrderShipment $shipment): string => trim((string) ($shipment->awb ?: $shipment->tracking_number)))
            ->filter()
            ->values()
            ->all();

        $view->with([
            'rajaOngkirEnabled' => komerce_shipping_delivery_enabled(),
            'orderIsPaid' => $this->order->payment_status === PaymentStatus::Paid,
            'rajaOngkirAwbs' => $awbs,
            'canPrintRajaOngkirLabel' => $shipments->contains(
                static fn (OrderShipment $shipment): bool => filled(data_get($shipment->metadata, 'komerce.order_no')),
            ),
            'printRajaOngkirRoute' => route('shopper.orders.fulfillment.print-label', $this->order),
        ]);

        return $view;
    }
}
