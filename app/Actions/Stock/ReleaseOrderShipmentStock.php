<?php

declare(strict_types=1);

namespace App\Actions\Stock;

use App\Models\OrderShipment;
use App\Models\OrderShipmentLine;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Shopper\Core\Models\Order;

final class ReleaseOrderShipmentStock
{
    public function handle(Order $order): void
    {
        $released = data_get($order->metadata, 'komerce.stock_released_at');

        if (is_string($released) && $released !== '') {
            return;
        }

        $shipments = OrderShipment::query()
            ->where('order_id', $order->id)
            ->with('lines')
            ->get();

        foreach ($shipments as $shipment) {
            foreach ($shipment->lines as $line) {
                $this->releaseLine($shipment, $line, $order);
            }
        }

        $metadata = is_array($order->metadata) ? $order->metadata : [];
        $komerce = is_array($metadata['komerce'] ?? null) ? $metadata['komerce'] : [];
        $komerce['stock_released_at'] = now()->toIso8601String();
        $metadata['komerce'] = $komerce;
        $order->forceFill(['metadata' => $metadata])->save();
    }

    private function releaseLine(OrderShipment $shipment, OrderShipmentLine $line, Order $order): void
    {
        $purchasable = $this->resolvePurchasable($line);

        if ($purchasable === null || ! method_exists($purchasable, 'mutateStock')) {
            return;
        }

        $inventoryId = (int) $shipment->inventory_id;
        $qty = (int) $line->qty;

        if ($inventoryId <= 0 || $qty <= 0) {
            return;
        }

        $purchasable->mutateStock(
            $inventoryId,
            $qty,
            0,
            'komerce_payment_expired',
            'Release stock after unpaid Komerce order expired',
            null,
            $order,
        );
    }

    private function resolvePurchasable(OrderShipmentLine $line): ?Model
    {
        $type = (string) $line->purchasable_type;
        $id = (int) $line->purchasable_id;

        if ($type === '' || $id <= 0) {
            return null;
        }

        $class = Relation::getMorphedModel($type) ?? $type;

        if (! is_string($class) || ! class_exists($class)) {
            return null;
        }

        /** @var Model|null $model */
        $model = $class::query()->find($id);

        return $model;
    }
}
