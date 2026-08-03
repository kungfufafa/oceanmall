<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Warehouse;

use App\Actions\Warehouse\SuggestAllocation;
use App\DTO\AllocationPlan;
use App\DTO\ShipmentDraft;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Shopper\Cart\Exceptions\InsufficientStockException;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Models\Inventory;
use Tests\TestCase;

final class SuggestAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_single_warehouse_fully_satisfies_cart_line(): void
    {
        $cart = $this->cart();
        $inventory = Inventory::factory()->create(['is_default' => true]);
        $product = Product::factory()->standard()->create();
        $product->mutateStock($inventory->id, 5);
        $this->addCartLine($cart, $product, 3);

        $plan = resolve(SuggestAllocation::class)->handle($cart, ['city' => 'Jakarta']);

        $this->assertInstanceOf(AllocationPlan::class, $plan);
        $this->assertCount(1, $plan->shipments);
        $this->assertInstanceOf(ShipmentDraft::class, $plan->shipments[0]);
        $this->assertSame($inventory->id, $plan->shipments[0]->inventory_id);
        $this->assertSame([
            [
                'purchasable_type' => $product->getMorphClass(),
                'purchasable_id' => $product->id,
                'qty' => 3,
            ],
        ], $plan->shipments[0]->lines);
    }

    public function test_line_quantity_splits_across_two_inventories_when_none_can_fully_satisfy_it(): void
    {
        $cart = $this->cart();
        $defaultInventory = Inventory::factory()->create(['is_default' => true]);
        $secondaryInventory = Inventory::factory()->create(['is_default' => false]);
        $product = Product::factory()->standard()->create();
        $product->mutateStock($defaultInventory->id, 2);
        $product->mutateStock($secondaryInventory->id, 3);
        $this->addCartLine($cart, $product, 4);

        $plan = resolve(SuggestAllocation::class)->handle($cart, []);

        $this->assertCount(2, $plan->shipments);
        $this->assertSame($defaultInventory->id, $plan->shipments[0]->inventory_id);
        $this->assertSame(2, $plan->shipments[0]->lines[0]['qty']);
        $this->assertSame($secondaryInventory->id, $plan->shipments[1]->inventory_id);
        $this->assertSame(2, $plan->shipments[1]->lines[0]['qty']);
    }

    public function test_insufficient_stock_throws_clear_exception(): void
    {
        $cart = $this->cart();
        $inventory = Inventory::factory()->create(['is_default' => true]);
        $product = Product::factory()->standard()->create();
        $product->mutateStock($inventory->id, 1);
        $this->addCartLine($cart, $product, 2);

        $this->expectException(InsufficientStockException::class);

        resolve(SuggestAllocation::class)->handle($cart, []);
    }

    public function test_stock_is_depleted_across_multiple_cart_lines_for_same_product(): void
    {
        $cart = $this->cart();
        $inventory = Inventory::factory()->create(['is_default' => true]);
        $product = Product::factory()->standard()->create();
        $product->mutateStock($inventory->id, 5);
        $this->addCartLine($cart, $product, 3);
        $this->addCartLine($cart, $product, 3);

        $this->expectException(InsufficientStockException::class);

        resolve(SuggestAllocation::class)->handle($cart, []);
    }

    public function test_missing_purchasable_throws_clear_exception(): void
    {
        $cart = $this->cart();
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => Product::class,
            'purchasable_id' => 999999,
            'quantity' => 1,
            'unit_price_amount' => 100000,
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Cart line ['.$cart->lines()->firstOrFail()->id.'] is missing purchasable');

        resolve(SuggestAllocation::class)->handle($cart, []);
    }

    private function cart(): Cart
    {
        return Cart::query()->create(['currency_code' => 'IDR']);
    }

    private function addCartLine(Cart $cart, Product $product, int $quantity): void
    {
        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => $quantity,
            'unit_price_amount' => 100000,
        ]);
    }
}
