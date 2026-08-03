<?php

declare(strict_types=1);

namespace App\Actions\Warehouse;

use App\Actions\Checkout\BuildShippingPackages;
use App\Actions\Checkout\FetchDeliveryRates;
use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use App\Models\OrderShipment;
use App\Models\OrderShipmentLine;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;

final class OverrideAllocation
{
    /**
     * @param  list<array<string, mixed>>  $moves
     */
    public function handle(Order $order, array $moves, ?User $actor = null): AllocationPlan
    {
        return DB::transaction(function () use ($order, $moves, $actor): AllocationPlan {
            $order->loadMissing('shippingAddress');
            $oldShippingTotal = $this->shipmentTotal($order);
            $normalizedMoves = $this->normalizeMoves($order, $moves);
            $affectedShipmentIds = $this->affectedShipmentIds($order, $normalizedMoves);

            $this->ensureShipmentsCanBeChanged($affectedShipmentIds);
            $this->ensureDestinationStock($order, $normalizedMoves);

            foreach ($normalizedMoves as $move) {
                $targetShipment = $this->moveLine($order, $move);
                $affectedShipmentIds[] = (int) $targetShipment->id;
            }

            $this->deleteEmptyShipments($order);
            $this->recalculateRates($order, array_values(array_unique($affectedShipmentIds)));
            $this->updateOrderShippingTotal($order, $oldShippingTotal);
            $this->writeAuditLogs($order, $normalizedMoves, $actor);

            return $this->allocationPlan($order);
        });
    }

    /**
     * @param  list<array<string, mixed>>  $moves
     * @return list<array{shipment_line_id: int, purchasable_type: string, purchasable_id: int, qty: int, from_inventory_id: int, to_inventory_id: int}>
     */
    private function normalizeMoves(Order $order, array $moves): array
    {
        if ($moves === []) {
            throw ValidationException::withMessages(['moves' => __('At least one allocation move is required.')]);
        }

        $normalized = [];

        foreach ($moves as $index => $move) {
            if (! is_array($move)) {
                throw ValidationException::withMessages(["moves.{$index}" => __('Allocation move must be an object.')]);
            }

            $quantity = (int) data_get($move, 'qty');
            $fromInventoryId = (int) data_get($move, 'from_inventory_id');
            $toInventoryId = (int) data_get($move, 'to_inventory_id');

            if ($quantity <= 0) {
                throw ValidationException::withMessages(["moves.{$index}.qty" => __('Quantity must be greater than zero.')]);
            }

            if ($fromInventoryId <= 0 || $toInventoryId <= 0 || $fromInventoryId === $toInventoryId) {
                throw ValidationException::withMessages(["moves.{$index}.to_inventory_id" => __('Choose a different destination inventory.')]);
            }

            $sourceLine = $this->sourceLine($order, $move, $fromInventoryId);

            if ($sourceLine->qty < $quantity) {
                throw ValidationException::withMessages(["moves.{$index}.qty" => __('Quantity exceeds the allocated shipment line quantity.')]);
            }

            $purchasableType = (string) (data_get($move, 'purchasable_type') ?: $sourceLine->purchasable_type);
            $purchasableId = (int) (data_get($move, 'purchasable_id') ?: $sourceLine->purchasable_id);

            if ($sourceLine->purchasable_type !== $purchasableType || (int) $sourceLine->purchasable_id !== $purchasableId) {
                throw ValidationException::withMessages(["moves.{$index}.shipment_line_id" => __('Shipment line does not match the requested purchasable.')]);
            }

            Inventory::query()->findOrFail($toInventoryId);

            $normalized[] = [
                'shipment_line_id' => (int) $sourceLine->id,
                'purchasable_type' => $purchasableType,
                'purchasable_id' => $purchasableId,
                'qty' => $quantity,
                'from_inventory_id' => $fromInventoryId,
                'to_inventory_id' => $toInventoryId,
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $move
     */
    private function sourceLine(Order $order, array $move, int $fromInventoryId): OrderShipmentLine
    {
        $query = OrderShipmentLine::query()
            ->whereHas('shipment', function ($query) use ($order, $fromInventoryId): void {
                $query->where('order_id', $order->id)
                    ->where('inventory_id', $fromInventoryId);
            });

        $lineId = data_get($move, 'shipment_line_id');

        if ($lineId !== null) {
            return (clone $query)->whereKey((int) $lineId)->firstOrFail();
        }

        return $query
            ->where('purchasable_type', (string) data_get($move, 'purchasable_type'))
            ->where('purchasable_id', (int) data_get($move, 'purchasable_id'))
            ->firstOrFail();
    }

    /**
     * @param  list<array{shipment_line_id: int, from_inventory_id: int, to_inventory_id: int}>  $moves
     * @return list<int>
     */
    private function affectedShipmentIds(Order $order, array $moves): array
    {
        $ids = [];

        foreach ($moves as $move) {
            $sourceLine = OrderShipmentLine::query()->with('shipment')->findOrFail($move['shipment_line_id']);

            if ($sourceLine->shipment) {
                $ids[] = (int) $sourceLine->shipment->id;
            }

            $targetShipmentId = OrderShipment::query()
                ->where('order_id', $order->id)
                ->where('inventory_id', $move['to_inventory_id'])
                ->value('id');

            if ($targetShipmentId !== null) {
                $ids[] = (int) $targetShipmentId;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<int>  $shipmentIds
     */
    private function ensureShipmentsCanBeChanged(array $shipmentIds): void
    {
        $shipments = OrderShipment::query()
            ->whereIn('id', $shipmentIds)
            ->lockForUpdate()
            ->get();

        foreach ($shipments as $shipment) {
            if (! in_array($shipment->status, ['pending', 'ready'], true)) {
                throw ValidationException::withMessages([
                    'shipment' => __('Allocation can only be overridden before shipment labels are created.'),
                ]);
            }

            if ($this->filled($shipment->awb) || $this->filled($shipment->tracking_number)) {
                throw ValidationException::withMessages([
                    'shipment' => __('Allocation can only be overridden before AWB or tracking number is assigned.'),
                ]);
            }
        }
    }

    private function filled(mixed $value): bool
    {
        return is_scalar($value) && trim((string) $value) !== '';
    }

    /**
     * @param  list<array{purchasable_type: string, purchasable_id: int, qty: int, from_inventory_id: int, to_inventory_id: int}>  $moves
     */
    private function ensureDestinationStock(Order $order, array $moves): void
    {
        $projected = $this->currentAllocationTotals($order);
        $destinationKeys = [];

        foreach ($moves as $move) {
            $fromKey = $this->stockKey($move['from_inventory_id'], $move['purchasable_type'], $move['purchasable_id']);
            $toKey = $this->stockKey($move['to_inventory_id'], $move['purchasable_type'], $move['purchasable_id']);

            $projected[$fromKey] = ($projected[$fromKey] ?? 0) - $move['qty'];
            $projected[$toKey] = ($projected[$toKey] ?? 0) + $move['qty'];
            $destinationKeys[$toKey] = [
                'inventory_id' => $move['to_inventory_id'],
                'purchasable_type' => $move['purchasable_type'],
                'purchasable_id' => $move['purchasable_id'],
            ];
        }

        foreach ($destinationKeys as $key => $destination) {
            $purchasable = $this->purchasable($destination['purchasable_type'], $destination['purchasable_id']);
            $available = (int) $purchasable->stockInventory($destination['inventory_id']);

            if ($available < ($projected[$key] ?? 0)) {
                throw ValidationException::withMessages([
                    'stock' => __('Destination inventory does not have enough stock for this allocation.'),
                ]);
            }
        }
    }

    /**
     * @return array<string, int>
     */
    private function currentAllocationTotals(Order $order): array
    {
        $totals = [];
        $shipments = OrderShipment::query()
            ->with('lines')
            ->where('order_id', $order->id)
            ->get();

        foreach ($shipments as $shipment) {
            foreach ($shipment->lines as $line) {
                $key = $this->stockKey((int) $shipment->inventory_id, $line->purchasable_type, (int) $line->purchasable_id);
                $totals[$key] = ($totals[$key] ?? 0) + (int) $line->qty;
            }
        }

        return $totals;
    }

    private function stockKey(int $inventoryId, string $purchasableType, int $purchasableId): string
    {
        return "{$inventoryId}:{$purchasableType}:{$purchasableId}";
    }

    private function purchasable(string $purchasableType, int $purchasableId): Model
    {
        $class = Relation::getMorphedModel($purchasableType) ?? $purchasableType;
        $model = app($class)->newQuery()->find($purchasableId);

        if (! $model instanceof Model) {
            throw ValidationException::withMessages([
                'purchasable' => __('Unable to find the allocated product.'),
            ]);
        }

        return $model;
    }

    /**
     * @param  array{shipment_line_id: int, purchasable_type: string, purchasable_id: int, qty: int, from_inventory_id: int, to_inventory_id: int}  $move
     */
    private function moveLine(Order $order, array $move): OrderShipment
    {
        $sourceLine = OrderShipmentLine::query()
            ->with('shipment')
            ->whereKey($move['shipment_line_id'])
            ->lockForUpdate()
            ->firstOrFail();

        if ($sourceLine->qty < $move['qty']) {
            throw ValidationException::withMessages(['qty' => __('Quantity exceeds the allocated shipment line quantity.')]);
        }

        if ($sourceLine->qty === $move['qty']) {
            $sourceLine->delete();
        } else {
            $sourceLine->update(['qty' => $sourceLine->qty - $move['qty']]);
        }

        $targetShipment = OrderShipment::query()->firstOrCreate(
            [
                'order_id' => $order->id,
                'inventory_id' => $move['to_inventory_id'],
            ],
            [
                'currency_code' => $order->currency_code,
                'status' => 'pending',
            ],
        );

        $targetLine = $targetShipment->lines()
            ->where('purchasable_type', $move['purchasable_type'])
            ->where('purchasable_id', $move['purchasable_id'])
            ->first();

        if ($targetLine instanceof OrderShipmentLine) {
            $targetLine->update(['qty' => $targetLine->qty + $move['qty']]);
        } else {
            $targetShipment->lines()->create([
                'purchasable_type' => $move['purchasable_type'],
                'purchasable_id' => $move['purchasable_id'],
                'qty' => $move['qty'],
            ]);
        }

        return $targetShipment;
    }

    private function deleteEmptyShipments(Order $order): void
    {
        OrderShipment::query()
            ->where('order_id', $order->id)
            ->whereDoesntHave('lines')
            ->delete();
    }

    /**
     * @param  list<int>  $shipmentIds
     */
    private function recalculateRates(Order $order, array $shipmentIds): void
    {
        $shipments = OrderShipment::query()
            ->with('lines')
            ->where('order_id', $order->id)
            ->whereIn('id', $shipmentIds)
            ->get();

        foreach ($shipments as $shipment) {
            if ($shipment->lines->isEmpty()) {
                continue;
            }

            $rates = resolve(FetchDeliveryRates::class)->handle(
                $this->shippingAddress($order),
                resolve(BuildShippingPackages::class)->handleFromLines($this->shipmentLines($shipment)),
                (int) $shipment->inventory_id,
            );

            $rate = $this->selectRate($rates, $shipment);

            if ($rate === null) {
                throw ValidationException::withMessages([
                    'rates' => __('No delivery rates are available for the updated allocation.'),
                ]);
            }

            $shipment->update([
                'carrier_code' => $this->rateString($rate, 'carrier_code'),
                'carrier_name' => $this->rateString($rate, 'carrier_name') ?? $this->rateString($rate, 'name'),
                'service_code' => $this->rateString($rate, 'service_code') ?? $this->rateString($rate, 'id'),
                'service_name' => $this->rateString($rate, 'service_name') ?? $this->rateString($rate, 'description'),
                'cost' => $this->rateCost($rate),
                'currency_code' => $this->rateString($rate, 'currency_code') ?? $this->rateString($rate, 'currency') ?? $order->currency_code,
                'metadata' => ['rate' => $rate],
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function shippingAddress(Order $order): array
    {
        $address = $order->shippingAddress;

        return [
            'first_name' => $address?->first_name ?? '',
            'last_name' => $address?->last_name ?? '',
            'street_address' => $address?->street_address ?? '',
            'street_address_plus' => $address?->street_address_plus,
            'postal_code' => $address?->postal_code ?? '',
            'city' => $address?->city ?? '',
            'state' => $address?->state,
            'phone_number' => $address?->phone,
            'country_name' => $address?->country_name,
        ];
    }

    /**
     * @return list<array{purchasable_type: string, purchasable_id: int, qty: int}>
     */
    private function shipmentLines(OrderShipment $shipment): array
    {
        return $shipment->lines
            ->map(static fn (OrderShipmentLine $line): array => [
                'purchasable_type' => $line->purchasable_type,
                'purchasable_id' => (int) $line->purchasable_id,
                'qty' => (int) $line->qty,
            ])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $rates
     * @return array<string, mixed>|null
     */
    private function selectRate(array $rates, OrderShipment $shipment): ?array
    {
        foreach ($rates as $rate) {
            if (! is_array($rate)) {
                continue;
            }

            if ($this->matchesPreviousRate($rate, $shipment)) {
                return $rate;
            }
        }

        $validRates = array_values(array_filter(
            $rates,
            fn (mixed $rate): bool => is_array($rate) && $this->rateCost($rate) > 0,
        ));

        usort($validRates, fn (array $left, array $right): int => $this->rateCost($left) <=> $this->rateCost($right));

        return $validRates[0] ?? null;
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    private function matchesPreviousRate(array $rate, OrderShipment $shipment): bool
    {
        $carrierCode = $this->rateString($rate, 'carrier_code');
        $serviceCode = $this->rateString($rate, 'service_code') ?? $this->rateString($rate, 'id');

        if ($carrierCode === null || $serviceCode === null) {
            return false;
        }

        $previousService = (string) $shipment->service_code;

        return $carrierCode === (string) $shipment->carrier_code
            && ($serviceCode === $previousService || "{$carrierCode}:{$previousService}" === $serviceCode);
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    private function rateCost(array $rate): int
    {
        return (int) (data_get($rate, 'amount')
            ?? data_get($rate, 'price')
            ?? data_get($rate, 'cost')
            ?? 0);
    }

    /**
     * @param  array<string, mixed>  $rate
     */
    private function rateString(array $rate, string $key): ?string
    {
        $value = data_get($rate, $key);

        return is_scalar($value) && $value !== '' ? (string) $value : null;
    }

    private function shipmentTotal(Order $order): int
    {
        return (int) OrderShipment::query()
            ->where('order_id', $order->id)
            ->sum('cost');
    }

    private function updateOrderShippingTotal(Order $order, int $oldShippingTotal): void
    {
        $newShippingTotal = $this->shipmentTotal($order);

        $order->update([
            'price_amount' => max(0, (int) $order->price_amount - $oldShippingTotal + $newShippingTotal),
        ]);
    }

    /**
     * @param  list<array{shipment_line_id: int, purchasable_type: string, purchasable_id: int, qty: int, from_inventory_id: int, to_inventory_id: int}>  $moves
     */
    private function writeAuditLogs(Order $order, array $moves, ?User $actor): void
    {
        foreach ($moves as $move) {
            DB::table('allocation_override_logs')->insert([
                'order_id' => $order->id,
                'user_id' => $actor?->id,
                'from_inventory_id' => $move['from_inventory_id'],
                'to_inventory_id' => $move['to_inventory_id'],
                'payload' => json_encode(['move' => $move], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }
    }

    private function allocationPlan(Order $order): AllocationPlan
    {
        $shipments = OrderShipment::query()
            ->with('lines')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get()
            ->map(fn (OrderShipment $shipment): ShipmentDraft => new ShipmentDraft(
                (int) $shipment->inventory_id,
                $this->shipmentLines($shipment),
            ))
            ->values()
            ->all();

        return new AllocationPlan($shipments);
    }
}
