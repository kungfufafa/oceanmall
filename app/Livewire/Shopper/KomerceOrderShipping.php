<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use App\Actions\Shipping\RefreshShipmentTracking;
use App\Actions\Shipping\SyncOrderShippingFromShipments;
use App\Actions\Warehouse\OverrideAllocation;
use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use App\Models\User;
use App\Services\Komerce\ShippingDeliveryClient;
use App\Support\OrderShipmentOpsPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\Component;
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

        $deliveryClient = resolve(ShippingDeliveryClient::class);
        $processedCount = 0;
        $lastError = null;

        foreach ($targetShipments as $shipment) {
            try {
                (new CreateRajaOngkirDeliveryForShipment((int) $shipment->id))->handle($deliveryClient);
                $processedCount++;
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

        if ($processedCount > 0) {
            $this->successMessage = "Berhasil mendaftarkan Delivery Order Komerce untuk pesanan #{$this->order->number}.";
        } elseif ($lastError !== null) {
            $this->overrideError = 'Gagal membuat Delivery Order Komerce: '.$lastError;
        }
    }

    public function processAllDeliveryOrders(): void
    {
        $this->processDeliveryOrder(null);
    }

    public function refreshTracking(?int $shipmentId = null): void
    {
        Gate::authorize('print-shipment-label', $this->order);
        $this->overrideError = null;
        $this->successMessage = null;

        $shipments = OrderShipment::query()
            ->where('order_id', $this->order->id)
            ->when($shipmentId !== null, fn ($q) => $q->where('id', $shipmentId))
            ->where(function ($q) {
                $q->whereNotNull('awb')->orWhereNotNull('tracking_number');
            })
            ->get();

        if ($shipments->isEmpty()) {
            $this->overrideError = 'Belum ada nomor resi AWB yang dapat dilacak.';
            return;
        }

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
            $this->successMessage = "Berhasil memperbarui status pelacakan kurir dari RajaOngkir.";
        } elseif ($lastError !== null) {
            $this->overrideError = 'Gagal memperbarui status pelacakan: '.$lastError;
        }
    }

    public function markPaidAndProcessDelivery(): void
    {
        Gate::authorize('print-shipment-label', $this->order);
        $this->overrideError = null;
        $this->successMessage = null;

        try {
            $updates = ['payment_status' => \Shopper\Core\Enum\PaymentStatus::Paid];
            if ($this->order->status === \Shopper\Core\Enum\OrderStatus::New) {
                $updates['status'] = \Shopper\Core\Enum\OrderStatus::Processing;
            }
            $this->order->update($updates);
            $this->order->refresh();

            $this->processDeliveryOrder(null);
            $this->successMessage = "Pesanan #{$this->order->number} berhasil ditandai Lunas (Paid) dan didaftarkan ke Komerce!";
        } catch (\Throwable $e) {
            report($e);
            $this->overrideError = 'Gagal menandai lunas & memproses Komerce: '.$e->getMessage();
        }
    }

    public function render(): View
    {
        $this->ensureShipmentsExist();
        $presenter = resolve(OrderShipmentOpsPresenter::class);
        $shipments = $presenter->shipments($this->order);
        $inventories = $presenter->inventories();
        $printableCount = $presenter->printableCount($shipments);
        $hasUnprocessed = collect($shipments)->contains(fn (array $s): bool => ! $s['can_print_label']);
        $hasTrackable = collect($shipments)->contains(fn (array $s): bool => filled($s['awb']) || filled($s['tracking_number']));
        $overridable = array_values(array_filter(
            $shipments,
            static fn (array $shipment): bool => (bool) $shipment['can_override'],
        ));

        return view('livewire.shopper.komerce-order-shipping', [
            'shipments' => $shipments,
            'inventories' => $inventories,
            'komerceEnabled' => komerce_enabled(),
            'canPrintAnyLabel' => $printableCount > 0,
            'printableShipmentCount' => $printableCount,
            'hasUnprocessedShipment' => $hasUnprocessed,
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
     * @return \Illuminate\Support\Collection<int, OrderShipment>
     */
    private function ensureShipmentsExist(): \Illuminate\Support\Collection
    {
        $existing = OrderShipment::query()->where('order_id', $this->order->id)->get();
        if ($existing->isNotEmpty()) {
            return $existing;
        }

        $defaultInventory = \Shopper\Core\Models\Inventory::query()->where('is_default', true)->first()
            ?? \Shopper\Core\Models\Inventory::query()->whereNotNull('rajaongkir_origin_id')->where('rajaongkir_origin_id', '!=', '')->first()
            ?? \Shopper\Core\Models\Inventory::query()->first();

        if (! $defaultInventory) {
            return collect();
        }

        $this->order->loadMissing(['items', 'shippingOption.carrier']);

        $shippingOption = $this->order->shippingOption;
        $carrierCode = 'jne';
        $carrierName = 'JNE';
        $serviceCode = 'REG';
        $serviceName = 'Reguler';
        $shippingCost = (int) ($this->order->shipping_total ?? 0);

        if ($shippingOption) {
            $carrierName = $shippingOption->carrier?->name ?? 'JNE';
            $carrierCode = strtolower($shippingOption->carrier?->slug ?? $carrierName);
            $serviceName = $shippingOption->name ?? 'Reguler';
            $serviceCode = strtoupper((string) (data_get($shippingOption->metadata, 'service_code') ?? $serviceName));
            if ($shippingCost === 0 && isset($shippingOption->price)) {
                $shippingCost = (int) $shippingOption->price;
            }
        }

        $metadata = is_array($this->order->metadata)
            ? $this->order->metadata
            : json_decode((string) $this->order->metadata, true) ?? [];

        if (isset($metadata['shipping']) && is_array($metadata['shipping'])) {
            $shippingMeta = $metadata['shipping'];
            $carrierCode = $shippingMeta['courier_code'] ?? $shippingMeta['courier'] ?? $carrierCode;
            $carrierName = $shippingMeta['courier_name'] ?? strtoupper((string) $carrierCode);
            $serviceCode = $shippingMeta['service_code'] ?? $shippingMeta['service'] ?? $serviceCode;
            $serviceName = $shippingMeta['service_name'] ?? $shippingMeta['service_description'] ?? $serviceName;
            if (! empty($shippingMeta['cost'])) {
                $shippingCost = (int) $shippingMeta['cost'];
            }
        }

        $shipment = OrderShipment::query()->create([
            'order_id' => $this->order->id,
            'inventory_id' => $defaultInventory->id,
            'status' => 'pending',
            'carrier_code' => strtolower((string) $carrierCode),
            'carrier_name' => $carrierName,
            'service_code' => $serviceCode,
            'service_name' => $serviceName,
            'cost' => $shippingCost,
            'currency_code' => $this->order->currency_code ?? 'IDR',
        ]);

        foreach ($this->order->items as $item) {
            $shipment->lines()->create([
                'purchasable_type' => $item->product_type,
                'purchasable_id' => $item->product_id,
                'qty' => max(1, (int) $item->quantity),
            ]);
        }

        return collect([$shipment]);
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
