<?php

declare(strict_types=1);

namespace App\Actions\Warehouse;

use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Models\Cart;
use Shopper\Core\Models\Inventory;

final readonly class SuggestAllocation
{
    /**
     * @param  array<string, mixed>  $destination
     */
    public function handle(Cart $cart, array $destination): AllocationPlan
    {
        $cart->loadMissing('lines.purchasable');

        $shipments = [];
        $shipmentOrder = [];
        $inventories = $this->eligibleInventories();
        $remainingStock = [];

        foreach ($cart->lines as $line) {
            $purchasable = $line->purchasable;

            if (! $purchasable instanceof Model) {
                throw new RuntimeException(sprintf(
                    'Cart line [%s] is missing purchasable [%s:%s].',
                    (string) $line->getKey(),
                    (string) $line->purchasable_type,
                    (string) $line->purchasable_id,
                ));
            }

            $remaining = (int) $line->quantity;
            $candidates = $this->candidates($inventories, $purchasable, $remainingStock);
            $allocated = 0;
            $fullCandidate = $this->firstFullCandidate($candidates, $remaining);

            if ($fullCandidate !== null) {
                $allocated += $this->addShipmentLine($shipments, $shipmentOrder, $fullCandidate['inventory']->id, $purchasable, $remaining);
                $this->depleteRemainingStock($remainingStock, $fullCandidate['inventory']->id, $purchasable, $remaining);
                $remaining = 0;
            } else {
                foreach ($candidates as $candidate) {
                    if ($remaining <= 0) {
                        break;
                    }

                    $quantity = min($candidate['available'], $remaining);
                    $allocated += $this->addShipmentLine($shipments, $shipmentOrder, $candidate['inventory']->id, $purchasable, $quantity);
                    $this->depleteRemainingStock($remainingStock, $candidate['inventory']->id, $purchasable, $quantity);
                    $remaining -= $quantity;
                }
            }

            if ($remaining > 0) {
                throw new InsufficientStockException($purchasable, $allocated, (int) $line->quantity);
            }
        }

        return new AllocationPlan(
            array_map(
                static fn (int $inventoryId): ShipmentDraft => new ShipmentDraft($inventoryId, $shipments[$inventoryId]),
                $shipmentOrder,
            ),
        );
    }

    /**
     * Inventories that can fulfill storefront shipments.
     *
     * When Komerce/RajaOngkir is enabled, only warehouses with a
     * `rajaongkir_origin_id` are used — otherwise rate quotes stay empty.
     *
     * @return Collection<int, Inventory>
     */
    private function eligibleInventories(): Collection
    {
        $query = Inventory::query();

        if (komerce_enabled()) {
            $query->whereNotNull('rajaongkir_origin_id')
                ->where('rajaongkir_origin_id', '!=', '');
        }

        return $query->get();
    }

    /**
     * @param  Collection<int, Inventory>  $inventories
     * @param  array<string, int>  $remainingStock
     * @return list<array{inventory: Inventory, available: int}>
     */
    private function candidates(Collection $inventories, Model $purchasable, array &$remainingStock): array
    {
        $candidates = [];

        foreach ($inventories as $inventory) {
            $available = $this->remainingAvailable($remainingStock, $inventory->id, $purchasable);

            if ($available > 0) {
                $candidates[] = [
                    'inventory' => $inventory,
                    'available' => $available,
                ];
            }
        }

        usort(
            $candidates,
            static function (array $left, array $right): int {
                $defaultComparison = (int) $right['inventory']->is_default <=> (int) $left['inventory']->is_default;

                if ($defaultComparison !== 0) {
                    return $defaultComparison;
                }

                $stockComparison = $right['available'] <=> $left['available'];

                if ($stockComparison !== 0) {
                    return $stockComparison;
                }

                return $left['inventory']->id <=> $right['inventory']->id;
            },
        );

        return $candidates;
    }

    /**
     * @param  array<string, int>  $remainingStock
     */
    private function remainingAvailable(array &$remainingStock, int $inventoryId, Model $purchasable): int
    {
        $key = $this->remainingStockKey($inventoryId, $purchasable);

        if (! array_key_exists($key, $remainingStock)) {
            $remainingStock[$key] = (int) $purchasable->stockInventory($inventoryId);
        }

        return $remainingStock[$key];
    }

    /**
     * @param  array<string, int>  $remainingStock
     */
    private function depleteRemainingStock(array &$remainingStock, int $inventoryId, Model $purchasable, int $quantity): void
    {
        if ($quantity <= 0) {
            return;
        }

        $remainingStock[$this->remainingStockKey($inventoryId, $purchasable)] -= $quantity;
    }

    private function remainingStockKey(int $inventoryId, Model $purchasable): string
    {
        return implode(':', [
            $inventoryId,
            $purchasable->getMorphClass(),
            (string) $purchasable->getKey(),
        ]);
    }

    /**
     * @param  list<array{inventory: Inventory, available: int}>  $candidates
     * @return array{inventory: Inventory, available: int}|null
     */
    private function firstFullCandidate(array $candidates, int $quantity): ?array
    {
        foreach ($candidates as $candidate) {
            if ($candidate['available'] >= $quantity) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @param  array<int, list<array{purchasable_type: string, purchasable_id: int, qty: int}>>  $shipments
     * @param  list<int>  $shipmentOrder
     */
    private function addShipmentLine(array &$shipments, array &$shipmentOrder, int $inventoryId, Model $purchasable, int $quantity): int
    {
        if ($quantity <= 0) {
            return 0;
        }

        if (! array_key_exists($inventoryId, $shipments)) {
            $shipments[$inventoryId] = [];
            $shipmentOrder[] = $inventoryId;
        }

        $shipments[$inventoryId][] = [
            'purchasable_type' => $purchasable->getMorphClass(),
            'purchasable_id' => (int) $purchasable->getKey(),
            'qty' => $quantity,
        ];

        return $quantity;
    }
}
