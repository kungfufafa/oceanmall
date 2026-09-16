<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Enum\DiscountApplyTo;
use Shopper\Core\Enum\DiscountEligibility;
use Shopper\Core\Enum\DiscountRequirement;
use Shopper\Core\Enum\DiscountType;
use Shopper\Core\Models\Discount;
use Tests\TestCase;

final class CartCouponApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_applies_and_removes_a_valid_coupon_like_vue(): void
    {
        $user = User::factory()->create();
        $this->seedCart($user);
        $this->createActiveDiscount();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/coupon', ['code' => 'ocean10'])
            ->assertOk()
            ->assertJsonPath('data.coupon_code', 'OCEAN10');

        $discount = $this->getJson('/api/v1/cart')->json('data.totals.discount');
        $this->assertIsInt($discount);
        $this->assertGreaterThan(0, $discount);

        $this->deleteJson('/api/v1/cart/coupon')
            ->assertOk()
            ->assertJsonPath('data.coupon_code', null)
            ->assertJsonPath('data.totals.discount', 0);
    }

    public function test_api_rejects_an_invalid_coupon_code(): void
    {
        $user = User::factory()->create();
        $this->seedCart($user);

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/coupon', ['code' => 'NOPE'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Kode kupon tidak valid.');
    }

    public function test_api_rejects_coupon_on_an_empty_cart(): void
    {
        $user = User::factory()->create();
        $this->createActiveDiscount();

        Sanctum::actingAs($user);

        $this->postJson('/api/v1/cart/coupon', ['code' => 'OCEAN10'])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Keranjang masih kosong.');
    }

    public function test_expo_cart_and_checkout_use_the_same_coupon_contract_as_vue(): void
    {
        $cart = file_get_contents(base_path('mobile/app/(tabs)/cart.tsx'));
        $checkout = file_get_contents(base_path('mobile/app/checkout.tsx'));
        $field = file_get_contents(base_path('mobile/components/coupon-field.tsx'));

        $this->assertIsString($cart);
        $this->assertIsString($checkout);
        $this->assertIsString($field);

        $this->assertStringContainsString("from '@/components/coupon-field'", $cart);
        $this->assertStringContainsString("from '@/components/coupon-field'", $checkout);
        $this->assertStringContainsString("'/cart/coupon'", $field);
        $this->assertStringContainsString("method: 'POST'", $field);
        $this->assertStringContainsString("method: 'DELETE'", $field);
        $this->assertStringContainsString('Kode kupon', $field);
        $this->assertStringContainsString('Terapkan', $field);
        $this->assertStringContainsString('Kupon diterapkan', $field);

        $this->assertStringContainsString('cart.totals.discount', $cart);
        $this->assertStringContainsString('checkout.cart.totals.subtotal', $checkout);
        $this->assertStringContainsString('checkout.cart.totals.discount', $checkout);
        $this->assertStringNotContainsString(
            'Subtotal {formatIdr(checkout.cart.totals.total)}',
            $checkout,
        );
    }

    private function seedCart(User $user): Product
    {
        $product = Product::factory()->standard()->create(['name' => 'Coupon API Product']);

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
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
}
