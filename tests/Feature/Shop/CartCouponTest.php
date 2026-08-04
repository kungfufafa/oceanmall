<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Cart\CartSessionManager;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\DiscountApplyTo;
use Shopper\Core\Enum\DiscountEligibility;
use Shopper\Core\Enum\DiscountRequirement;
use Shopper\Core\Enum\DiscountType;
use Shopper\Core\Models\Discount;
use Shopper\Core\Models\Product;
use Tests\TestCase;

final class CartCouponTest extends TestCase
{
    use RefreshDatabase;

    private function seedCartWithProduct(?User $user = null): Product
    {
        $product = Product::factory()->standard()->create(['name' => 'Coupon Product']);

        $cart = resolve(CartSessionManager::class)->create([
            'currency_code' => 'IDR',
            'customer_id' => $user?->id,
        ]);

        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 100_000,
        ]);

        return $product;
    }

    private function createActiveDiscount(string $code = 'OCEAN10'): Discount
    {
        return Discount::query()->create([
            'code' => $code,
            'is_active' => true,
            'type' => DiscountType::Percentage,
            'value' => 10,
            'apply_to' => DiscountApplyTo::Order->value,
            'min_required' => DiscountRequirement::None->value,
            'eligibility' => DiscountEligibility::Everyone->value,
            'usage_limit' => null,
            'usage_limit_per_user' => false,
            'total_use' => 0,
            'start_at' => now()->subDay(),
            'end_at' => now()->addYear(),
        ]);
    }

    public function test_can_apply_and_remove_valid_coupon(): void
    {
        $this->seedCartWithProduct();
        $this->createActiveDiscount();

        $this->from(route('shop.cart'))
            ->post(route('shop.cart.coupon.store'), ['code' => 'ocean10'])
            ->assertRedirect(route('shop.cart'));

        $cart = resolve(CartSessionManager::class)->current();
        $this->assertSame('OCEAN10', $cart?->coupon_code);

        $this->from(route('shop.cart'))
            ->delete(route('shop.cart.coupon.destroy'))
            ->assertRedirect(route('shop.cart'));

        $this->assertNull(resolve(CartSessionManager::class)->current()?->coupon_code);
    }

    public function test_rejects_invalid_coupon_code(): void
    {
        $this->seedCartWithProduct();

        $this->from(route('shop.cart'))
            ->post(route('shop.cart.coupon.store'), ['code' => 'NOPE'])
            ->assertRedirect(route('shop.cart'))
            ->assertSessionHasErrors('code');
    }
}
