<?php

declare(strict_types=1);

namespace App\Livewire\Shopper;

use App\Actions\Warehouse\OverrideAllocation;
use App\Models\User;
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

    public function mount(Order $order): void
    {
        Gate::authorize('print-shipment-label', $order);

        $this->order = $order;
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

    public function render(): View
    {
        $presenter = resolve(OrderShipmentOpsPresenter::class);
        $shipments = $presenter->shipments($this->order);
        $inventories = $presenter->inventories();
        $printableCount = $presenter->printableCount($shipments);
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
