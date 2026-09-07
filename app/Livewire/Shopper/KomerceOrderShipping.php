<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use App\Actions\Checkout\SyncKomercePaymentStatus;
use App\Actions\Shipping\EnsureOrderShipments;
use App\Actions\Shipping\ReconcileKomerceShipment;
use App\Actions\Shipping\RefreshShipmentTracking;
use App\Actions\Shipping\SyncOrderShippingFromShipments;
use App\Actions\Warehouse\OverrideAllocation;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Models\User;
use App\Support\OrderShipmentOpsPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;

final class KomerceOrderShipping extends Component
{
    #[Locked]
    public Order $order;

    public ?int $shipment_line_id = null;

    public int $qty = 1;

    public ?int $from_inventory_id = null;

    public ?int $to_inventory_id = null;

    public ?string $overrideError = null;

    public ?string $successMessage = null;

    public function mount(Order $order): void
    {
        Gate::authorize('print-shipment-label', $order);

        $this->order = $order;
        $this->ensureShipmentsExist();
        $this->seedOverrideDefaults();
    }

    public function updatedShipmentLineId(): void
    {
        $presenter = resolve(OrderShipmentOpsPresenter::class);
        foreach ($presenter->shipments($this->order) as $shipment) {
            foreach ($shipment['lines'] as $line) {
                if ((int) $line['id'] === (int) $this->shipment_line_id) {
                    $this->from_inventory_id = $shipment['inventory_id'];

                    return;
                }
            }
        }
    }

    public function applyOverride(OverrideAllocation $overrideAllocation): void
    {
        Gate::authorize('override-allocation', $this->order);
        $this->overrideError = null;
        $this->successMessage = null;

        $this->validate([
            'shipment_line_id' => ['required', 'integer'],
            'qty' => ['required', 'integer', 'min:1'],
            'from_inventory_id' => ['required', 'integer'],
            'to_inventory_id' => ['required', 'integer', 'different:from_inventory_id'],
        ]);

        $actor = auth()->user();
        if (! $actor instanceof User) {
            $this->overrideError = 'Authenticated admin user is required.';

            return;
        }

        try {
            $overrideAllocation->handle($this->order, [[
                'shipment_line_id' => $this->shipment_line_id,
                'qty' => $this->qty,
                'from_inventory_id' => $this->from_inventory_id,
                'to_inventory_id' => $this->to_inventory_id,
            ]], $actor);
            $this->successMessage = 'Stok berhasil dipindahkan ke gudang tujuan.';
        } catch (ValidationException $e) {
            $this->overrideError = collect($e->errors())->flatten()->first() ?? 'Could not move stock.';

            return;
        } catch (\Throwable $e) {
            report($e);
            $this->overrideError = $e->getMessage() ?: 'Could not move stock.';

            return;
        }

        $this->order->refresh();
        $this->seedOverrideDefaults();
        $this->dispatch('order.updated');
    }

    public function processDeliveryOrder(?int $shipmentId = null): void
    {
        Gate::authorize('print-shipment-label', $this->order);
        $this->overrideError = null;
        $this->successMessage = null;

        if ($this->order->payment_status !== PaymentStatus::Paid) {
            $this->overrideError = 'Lunasi pesanan dulu sebelum mendaftarkan pickup dan resi Komerce.';

            return;
        }

        $shipments = $this->ensureShipmentsExist();

        if ($shipmentId !== null) {
            $targetShipments = $shipments->where('id', $shipmentId);
        } else {
            $targetShipments = $shipments;
        }

        if ($targetShipments->isEmpty()) {
            $this->overrideError = 'Tidak ada shipment pengiriman yang dapat diproses.';

            return;
        }

        $issuedCount = 0;
        $pickupPendingCount = 0;
        $lastError = null;

        foreach ($targetShipments as $shipment) {
            $alreadyLabeled = filled($shipment->awb) || filled($shipment->tracking_number);
            if ($alreadyLabeled) {
                continue;
            }

            try {
                (new CreateRajaOngkirDeliveryForShipment((int) $shipment->id))->handle();
                $shipment->refresh();
                if (filled($shipment->awb) || filled($shipment->tracking_number)) {
                    $issuedCount++;
                } else {
                    $pickupPendingCount++;
                }
            } catch (\Throwable $e) {
                report($e);
                $lastError = $e->getMessage();
            }
        }

        $this->order->refresh();
        resolve(SyncOrderShippingFromShipments::class)->handle($this->order);
        $this->order->refresh();

        $this->seedOverrideDefaults();
        $this->dispatch('order.updated');
        $this->dispatch('order.shipping.created');

        if ($issuedCount > 0) {
            $this->successMessage = "Nomor resi Komerce terbit untuk pesanan #{$this->order->number}.";
        } elseif ($pickupPendingCount > 0) {
            $this->successMessage = 'Pickup Komerce diminta. Nomor resi muncul setelah kurir memproses paket.';
        } elseif ($lastError !== null) {
            $this->overrideError = 'Gagal pickup/resi Komerce: '.$lastError;
        }
    }

    public function processAllDeliveryOrders(): void
    {
        $this->processDeliveryOrder(null);
    }

    public function requestPickup(?int $shipmentId = null): void
    {
        $this->processDeliveryOrder($shipmentId);
    }

    public function refreshTracking(?int $shipmentId = null): void
    {
        Gate::authorize('print-shipment-label', $this->order);
        $this->overrideError = null;
        $this->successMessage = null;

        $shipments = OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->when($shipmentId !== null, fn ($q) => $q->where('id', $shipmentId))
            ->get();

        if ($shipments->isEmpty()) {
            $this->overrideError = 'Belum ada shipment yang dapat dilacak.';

            return;
        }

        $reconcile = resolve(ReconcileKomerceShipment::class);
        $trackable = collect();

        foreach ($shipments as $shipment) {
            if (filled(data_get($shipment->metadata, 'komerce.order_no'))) {
                try {
                    $shipment = $reconcile->handle($shipment);
                } catch (\Throwable $e) {
                    report($e);
                    $this->overrideError = 'Gagal menyelaraskan data Komerce: '.$e->getMessage();
                }
            }

            if (filled($shipment->awb) || filled($shipment->tracking_number)) {
                $trackable->push($shipment);
            }
        }

        if ($trackable->isEmpty()) {
            $this->overrideError = $this->overrideError
                ?? 'Belum ada nomor resi AWB. Request pickup dulu, atau tunggu Komerce menerbitkan resi.';

            return;
        }

        $shipments = $trackable;
        $refresher = resolve(RefreshShipmentTracking::class);
        $refreshedCount = 0;
        $lastError = null;

        foreach ($shipments as $shipment) {
            try {
                $refresher->handle($shipment);
                $refreshedCount++;
            } catch (\Throwable $e) {
                report($e);
                $lastError = $e->getMessage();
            }
        }

        $this->order->refresh();
        resolve(SyncOrderShippingFromShipments::class)->handle($this->order);
        $this->order->refresh();

        $this->seedOverrideDefaults();
        $this->dispatch('order.updated');

        if ($refreshedCount > 0) {
            $this->successMessage = 'Berhasil memperbarui status pelacakan kurir dari RajaOngkir.';
        } elseif ($lastError !== null) {
            $this->overrideError = 'Gagal memperbarui status pelacakan: '.$lastError;
        }
    }

    public function markPaidAndProcessDelivery(): void
    {
        Gate::authorize('print-shipment-label', $this->order);
        $this->overrideError = null;
        $this->successMessage = null;

        $result = resolve(SyncKomercePaymentStatus::class)->handle($this->order);
        $this->order->refresh();

        if ($this->order->payment_status !== PaymentStatus::Paid) {
            $this->overrideError = match ($result) {
                'no_payment' => 'Tidak ada referensi pembayaran Komerce. Jangan tandai lunas manual.',
                default => 'Komerce belum menandai pembayaran lunas. Status toko tidak diubah.',
            };

            return;
        }

        $this->processDeliveryOrder(null);
        if ($this->overrideError === null) {
            $this->successMessage = "Pembayaran selaras dengan Komerce. Pickup/resi diproses untuk #{$this->order->number}.";
        }
    }

    public function render(): View
    {
        $this->ensureShipmentsExist();
        $presenter = resolve(OrderShipmentOpsPresenter::class);
        $shipments = $presenter->shipments($this->order);
        $inventories = $presenter->inventories();
        $printableCount = $presenter->printableCount($shipments);
        $hasUnprocessed = collect($shipments)->contains(
            fn (array $s): bool => ! $s['can_print_label'] || ($s['needs_pickup'] ?? false),
        );
        $needsPickup = collect($shipments)->contains(fn (array $s): bool => (bool) ($s['needs_pickup'] ?? false));
        $hasTrackable = collect($shipments)->contains(fn (array $s): bool => filled($s['awb']) || filled($s['tracking_number']));
        $overridable = array_values(array_filter(
            $shipments,
            static fn (array $shipment): bool => (bool) $shipment['can_override'],
        ));

        return view('livewire.shopper.komerce-order-shipping', [
            'shipments' => $shipments,
            'inventories' => $inventories,
            'komerceEnabled' => komerce_shipping_delivery_enabled(),
            'canPrintAnyLabel' => $printableCount > 0,
            'printableShipmentCount' => $printableCount,
            'hasUnprocessedShipment' => $hasUnprocessed,
            'needsPickup' => $needsPickup,
            'orderIsPaid' => $this->order->payment_status === PaymentStatus::Paid,
            'hasTrackableShipment' => $hasTrackable,
            'overridableShipments' => $overridable,
            'lineOptions' => collect($overridable)->flatMap(
                static fn (array $shipment): array => collect($shipment['lines'])->map(
                    static fn (array $line): array => [
                        'id' => $line['id'],
                        'label' => $line['name'].' ×'.$line['qty'].' · '.($shipment['inventory_name'] ?? 'Warehouse'),
                        'inventory_id' => $shipment['inventory_id'],
                    ],
                )->all(),
            )->values()->all(),
        ]);
    }

    /**
     * @return Collection<int, OrderShipment>
     */
    private function ensureShipmentsExist(): Collection
    {
        return resolve(EnsureOrderShipments::class)->handle($this->order);
    }

    private function seedOverrideDefaults(): void
    {
        $presenter = resolve(OrderShipmentOpsPresenter::class);
        $shipments = $presenter->shipments($this->order);
        $overridable = collect($shipments)->firstWhere('can_override', true);
        $inventories = $presenter->inventories();

        if ($overridable === null) {
            $this->shipment_line_id = null;
            $this->from_inventory_id = null;
            $this->to_inventory_id = null;

            return;
        }

        $firstLine = $overridable['lines'][0] ?? null;
        $this->shipment_line_id = $firstLine['id'] ?? null;
        $this->from_inventory_id = $overridable['inventory_id'] ?? null;
        $this->to_inventory_id = collect($inventories)
            ->first(fn (array $inventory): bool => $inventory['id'] !== $this->from_inventory_id)['id'] ?? null;
        $this->qty = 1;
    }
}
