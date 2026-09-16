<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Price;
use Tests\TestCase;

final class CatalogCartStockApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_product_exposes_available_stock_like_the_cart(): void
    {
        $product = $this->stockedProduct(3);

        $this->getJson('/api/v1/catalog/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.slug', $product->slug)
            ->assertJsonPath('data.available_stock', 3);
    }

    public function test_catalog_to_cart_rejects_quantity_above_available_stock(): void
    {
        $user = User::factory()->create();
        $product = $this->stockedProduct(2);

        Sanctum::actingAs($user);

        $this->getJson('/api/v1/catalog/products/'.$product->slug)
            ->assertOk()
            ->assertJsonPath('data.available_stock', 2);

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 5,
        ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Stok tidak mencukupi.');

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonCount(0, 'data.lines');

        $this->postJson('/api/v1/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ])
            ->assertOk()
            ->assertJsonPath('data.lines.0.quantity', 2)
            ->assertJsonPath('data.lines.0.available_stock', 2);

        $lineId = $this->getJson('/api/v1/cart')->json('data.lines.0.id');
        $this->assertIsInt($lineId);

        $this->patchJson('/api/v1/cart/items/'.$lineId, ['quantity' => 3])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Stok tidak mencukupi.');

        $this->getJson('/api/v1/cart')
            ->assertOk()
            ->assertJsonPath('data.lines.0.quantity', 2)
            ->assertJsonPath('data.lines.0.available_stock', 2);
    }

    public function test_cart_update_rejects_insufficient_stock_and_keeps_the_line(): void
    {
        $user = User::factory()->create();
        $product = $this->stockedProduct(1);

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);
        $line = CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 50_000,
        ]);

        Sanctum::actingAs($user);

        $this->patchJson('/api/v1/cart/items/'.$line->id, ['quantity' => 5])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Stok tidak mencukupi.');

        $this->assertSame(1, (int) $line->fresh()->quantity);
    }

    public function test_expo_product_caps_qty_at_available_stock_not_a_hardcoded_ten(): void
    {
        $page = file_get_contents(base_path('mobile/app/product/[slug].tsx'));

        $this->assertIsString($page);
        $this->assertStringContainsString('available_stock', $page);
        $this->assertStringNotContainsString('Math.min(10, value + 1)', $page);
    }

    public function test_vue_cart_stepper_uses_available_stock_as_max(): void
    {
        $page = file_get_contents(resource_path('js/pages/shop/cart.vue'));

        $this->assertIsString($page);
        $this->assertStringContainsString('availableStock', $page);
        $this->assertStringContainsString(':max="lineMax(line)"', $page);
    }

    private function stockedProduct(int $qty): Product
    {
        $inventory = Inventory::factory()->create(['is_default' => true]);
        $product = Product::factory()->standard()->create([
            'name' => 'Stok Journey Product',
            'slug' => 'stok-journey-product-'.$qty,
            'published_at' => now()->subDay(),
        ]);
        $product->mutateStock($inventory->id, $qty);

        $currency = Currency::query()->where('code', shopper_currency())->first()
            ?? Currency::factory()->create([
                'code' => shopper_currency(),
                'name' => shopper_currency(),
                'symbol' => shopper_currency(),
                'format' => '1,234.56',
            ]);
        Price::query()->create([
            'priceable_type' => $product->getMorphClass(),
            'priceable_id' => $product->id,
            'amount' => 99_000,
            'currency_id' => $currency->id,
        ]);

        return $product;
    }
}
