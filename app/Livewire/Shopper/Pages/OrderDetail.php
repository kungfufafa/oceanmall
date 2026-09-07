<?php

declare(strict_types=1);

namespace App\Livewire\Shopper\Pages;

use App\Actions\Checkout\CancelShopperOrderViaKomerce;
use App\Actions\Checkout\SyncKomercePaymentStatus;
use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Models\OrderShipment;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Events\Orders\OrderCompleted;
use Shopper\Livewire\Pages\Order\Detail as ShopperOrderDetail;

/**
 * Shopper order detail stays the store shell. Paid / shipped / cancelled
 * must go through Komerce; this page is the bridge, not a second source of truth.
 */
final class OrderDetail extends ShopperOrderDetail
{
    public function markPaidAction(): Action
    {
        $action = parent::markPaidAction();

        if (! komerce_payment_enabled() && ! qrisly_enabled()) {
            return $action;
        }

        return $action
            ->label('Sinkronkan pembayaran Komerce')
            ->action(function (): void {
                $result = resolve(SyncKomercePaymentStatus::class)->handle($this->order);
                $this->order->refresh();
                $this->dispatch('order.updated');

                match ($result) {
                    'handled', 'already_paid' => Notification::make()
                        ->title('Pembayaran diselaraskan dari Komerce.')
                        ->success()
                        ->send(),
                    'no_payment' => Notification::make()
                        ->title('Tidak ada referensi pembayaran Komerce.')
                        ->body('Jangan tandai lunas manual. Tunggu VA/QRIS atau webhook Komerce.')
                        ->danger()
                        ->send(),
                    default => Notification::make()
                        ->title('Komerce belum menandai pembayaran lunas.')
                        ->body('Status toko tidak diubah. Cek ulang di Komerce.')
                        ->warning()
                        ->send(),
                };
            });
    }

    public function markCompleteAction(): Action
    {
        $action = parent::markCompleteAction();

        if (! komerce_shipping_delivery_enabled()) {
            return $action;
        }

        return $action
            ->visible(fn (): bool => $this->order->isProcessing()
                && $this->order->isPaid()
                && $this->allShipmentsDelivered())
            ->action(function (): void {
                if (! $this->allShipmentsDelivered()) {
                    Notification::make()
                        ->title('Pesanan belum selesai di Komerce.')
                        ->body('Selesai hanya setelah resi berstatus delivered / customer konfirmasi diterima.')
                        ->danger()
                        ->send();

                    return;
                }

                $this->order->update(['status' => OrderStatus::Completed]);
                $this->order->refresh();
                $this->dispatch('order.updated');
                event(new OrderCompleted($this->order));

                Notification::make()
                    ->title('Pesanan diselesaikan setelah Komerce menandai terkirim.')
                    ->success()
                    ->send();
            });
    }

    public function cancelOrderAction(): Action
    {
        $action = parent::cancelOrderAction();

        if (! komerce_payment_enabled() && ! qrisly_enabled() && ! komerce_shipping_delivery_enabled()) {
            return $action;
        }

        return $action->action(function (): void {
            resolve(CancelShopperOrderViaKomerce::class)->handle($this->order);
            $this->order->refresh();
            $this->dispatch('order.updated');

            Notification::make()
                ->title('Pesanan dibatalkan di toko dan di Komerce.')
                ->success()
                ->send();
        });
    }

    public function capturePaymentAction(): Action
    {
        $action = parent::capturePaymentAction();

        if (komerce_payment_enabled() || qrisly_enabled()) {
            return $action->visible(false);
        }

        return $action;
    }

    public function archiveAction(): Action
    {
        $action = parent::archiveAction();

        if (komerce_payment_enabled() || qrisly_enabled() || komerce_shipping_delivery_enabled()) {
            return $action
                ->visible(false)
                ->action(function (): void {
                    Notification::make()
                        ->title('Jangan arsip lewat Shopper.')
                        ->body('Batalkan pesanan agar pembayaran/delivery Komerce ikut ditutup.')
                        ->danger()
                        ->send();
                });
        }

        return $action;
    }

    private function allShipmentsDelivered(): bool
    {
        $shipments = OrderShipment::query()->where('order_id', $this->order->id)->get();

        if ($shipments->isEmpty()) {
            return false;
        }

        return $shipments->every(
            static fn (OrderShipment $shipment): bool => strtolower((string) $shipment->status) === NormalizeShipmentStatus::DELIVERED,
        );
    }
}
