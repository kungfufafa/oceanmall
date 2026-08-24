<?php

declare(strict_types=1);

namespace App\Stock;

use App\Support\CheckoutAllocationContext;
use Illuminate\Database\Eloquent\Model;
use Shopper\Core\Contracts\StockAllocator;
use Shopper\Core\Models\Contracts\Stockable;
use Shopper\Core\Stock\PriorityStockAllocator;
use Shopper\Core\Stock\StockAllocation;

final readonly class AllocationPlanStockAllocator implements StockAllocator
{
    public function __construct(
        private CheckoutAllocationContext $context,
        private PriorityStockAllocator $fallback,
    ) {}

    /**
     * @return array<int, StockAllocation>
     */
    public function allocate(Stockable $product, int $quantity): array
    {
        $plan = $this->context->get();

        if ($plan === null || ! ($product instanceof Model)) {
            return $this->fallback->allocate($product, $quantity);
        }

        $morphClass = $product->getMorphClass();
        $productId = (int) $product->getKey();

        $allocations = [];

        foreach ($plan->shipments as $shipmentDraft) {
            foreach ($shipmentDraft->lines as $line) {
                if ($line['purchasable_type'] === $morphClass && $line['purchasable_id'] === $productId) {
                    $allocations[] = new StockAllocation($shipmentDraft->inventory_id, $line['qty']);
                }
            }
        }

        if ($allocations !== []) {
            return $allocations;
        }

        return $this->fallback->allocate($product, $quantity);
    }
}
