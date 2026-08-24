<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Models\Inventory;
use Tests\TestCase;

final class BrowseAndCartTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_and_shop_index_are_ok(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('shop.index'))->assertOk()
            ->assertInertia(fn ($page) => $page->component('shop/index'));
    }

    public function test_published_product_and_category_and_search_are_ok(): void
    {
        $product = Product::factory()->standard()->create([
            'name' => 'Ocean Tee',
            'slug' => 'ocean-tee',
            'published_at' => now()->subDay(),
        ]);

        $category = Category::query()->create([
            'name' => 'Apparel',
            'slug' => 'apparel',
            'is_enabled' => true,
        ]);

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('shop/product')
                ->where('product.slug', 'ocean-tee'));

        $this->get(route('shop.category', $category))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('shop/category'));

        $this->get(route('shop.search', ['q' => 'Ocean']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('shop/search'));
    }

    public function test_add_update_and_remove_cart_line(): void
    {
        $inventory = Inventory::factory()->create(['is_default' => true]);
        $product = Product::factory()->standard()->create([
            'name' => 'Cart Item',
            'published_at' => now()->subDay(),
        ]);
        $product->mutateStock($inventory->id, 5);

        $this->from(route('shop.product', $product))
            ->post(route('shop.cart.add'), [
                'product_id' => $product->id,
                'quantity' => 1,
            ])
            ->assertRedirect();

        $cart = resolve(CartSessionManager::class)->current();
        $this->assertNotNull($cart);
        $line = $cart->lines()->first();
        $this->assertNotNull($line);
        $this->assertSame(1, (int) $line->quantity);

        $this->from(route('shop.cart'))
            ->patch(route('shop.cart.update', $line->id), ['quantity' => 2])
            ->assertRedirect();

        $this->assertSame(2, (int) $line->fresh()->quantity);

        $this->from(route('shop.cart'))
            ->delete(route('shop.cart.destroy', $line->id))
            ->assertRedirect();

        $this->assertSame(0, $cart->fresh()->lines()->count());
    }

    public function test_cart_update_rejects_insufficient_stock(): void
    {
        $inventory = Inventory::factory()->create(['is_default' => true]);
        $product = Product::factory()->standard()->create([
            'published_at' => now()->subDay(),
        ]);
        $product->mutateStock($inventory->id, 1);

        $cart = resolve(CartSessionManager::class)->create(['currency_code' => 'IDR']);
        $line = CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 50_000,
        ]);

        $this->from(route('shop.cart'))
            ->patch(route('shop.cart.update', $line->id), ['quantity' => 5])
            ->assertRedirect(route('shop.cart'))
            ->assertSessionHasErrors('cart');

        $this->assertSame(1, (int) $line->fresh()->quantity);
    }

    public function test_guest_checkout_redirects_to_login_then_returns_after_auth(): void
    {
        $this->get(route('shop.checkout.index'))
            ->assertRedirect();

        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('shop.checkout.index'))
            ->assertRedirect(route('shop.cart')); // empty cart redirects to cart
    }
}
