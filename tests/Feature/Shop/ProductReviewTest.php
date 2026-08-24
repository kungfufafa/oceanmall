<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderItem;
use Shopper\Core\Models\Review;
use Tests\TestCase;

final class ProductReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $manifest = public_path('build/manifest.json');
        $this->withHeader(
            'X-Inertia-Version',
            file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        );
    }

    /**
     * @return array{0: User, 1: Product, 2: Order}
     */
    private function buyerWithCompletedOrder(): array
    {
        $user = User::factory()->create();
        /** @var Product $product */
        $product = Product::factory()->standard()->create([
            'name' => 'Reviewable Phone',
            'slug' => 'reviewable-phone',
            'is_visible' => true,
            'published_at' => now()->subDay(),
        ]);

        $order = Order::factory()->create([
            'customer_id' => $user->id,
            'status' => OrderStatus::Completed,
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => ShippingStatus::Delivered,
            'currency_code' => 'IDR',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'product_type' => $product->getMorphClass(),
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 100000,
        ]);

        return [$user, $product, $order];
    }

    public function test_product_page_shows_only_approved_reviews(): void
    {
        [, $product] = $this->buyerWithCompletedOrder();
        $author = User::factory()->create(['first_name' => 'Siti', 'last_name' => 'Aminah']);

        Review::query()->create([
            'rating' => 5,
            'title' => 'Bagus',
            'content' => 'Sangat puas',
            'approved' => true,
            'is_recommended' => true,
            'reviewrateable_type' => $product->getMorphClass(),
            'reviewrateable_id' => $product->id,
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->id,
        ]);

        Review::query()->create([
            'rating' => 1,
            'title' => 'Pending',
            'content' => 'Belum disetujui',
            'approved' => false,
            'is_recommended' => false,
            'reviewrateable_type' => $product->getMorphClass(),
            'reviewrateable_id' => $product->id,
            'author_type' => $author->getMorphClass(),
            'author_id' => $author->id,
        ]);

        $this->get(route('shop.product', $product))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('shop/product')
                ->has('reviews.items', 1)
                ->where('reviews.totalCount', 1)
                ->where('reviews.items.0.title', 'Bagus')
            );
    }

    public function test_verified_buyer_can_submit_pending_review(): void
    {
        [$user, $product] = $this->buyerWithCompletedOrder();

        $this->actingAs($user)
            ->from(route('shop.product', $product))
            ->post(route('shop.product.reviews.store', $product), [
                'rating' => 4,
                'title' => 'Oke',
                'content' => 'Sesuai deskripsi',
            ])
            ->assertRedirect(route('shop.product', $product));

        $this->assertDatabaseHas(shopper_table('reviews'), [
            'reviewrateable_id' => $product->id,
            'author_id' => $user->id,
            'rating' => 4,
            'approved' => false,
        ]);
    }

    public function test_non_buyer_cannot_submit_review(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->standard()->create([
            'slug' => 'no-buy-product',
            'is_visible' => true,
            'published_at' => now()->subDay(),
        ]);

        $this->actingAs($user)
            ->from(route('shop.product', $product))
            ->post(route('shop.product.reviews.store', $product), [
                'rating' => 5,
                'content' => 'Spam',
            ])
            ->assertRedirect(route('shop.product', $product))
            ->assertSessionHasErrors('review');
    }

    public function test_duplicate_review_is_rejected(): void
    {
        [$user, $product] = $this->buyerWithCompletedOrder();

        Review::query()->create([
            'rating' => 5,
            'title' => 'Sudah',
            'content' => 'Ada',
            'approved' => false,
            'is_recommended' => true,
            'reviewrateable_type' => $product->getMorphClass(),
            'reviewrateable_id' => $product->id,
            'author_type' => $user->getMorphClass(),
            'author_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('shop.product.reviews.store', $product), [
                'rating' => 3,
                'content' => 'Lagi',
            ])
            ->assertSessionHasErrors('review');
    }
}
