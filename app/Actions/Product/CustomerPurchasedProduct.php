<?php

declare(strict_types=1);

namespace App\Actions\Product;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\Review;

final class CustomerPurchasedProduct
{
    public function handle(User $user, Product $product): bool
    {
        $variantIds = $product->variants()->pluck('id');

        return Order::query()
            ->where('customer_id', $user->id)
            ->where(function ($query): void {
                $query->where('status', OrderStatus::Completed)
                    ->orWhere('shipping_status', ShippingStatus::Delivered);
            })
            ->whereHas('items', function ($query) use ($product, $variantIds): void {
                $query->where(function ($inner) use ($product, $variantIds): void {
                    $inner->where(function ($productItem) use ($product): void {
                        $productItem
                            ->where('product_type', $product->getMorphClass())
                            ->where('product_id', $product->id);
                    });

                    if ($variantIds->isNotEmpty()) {
                        $inner->orWhere(function ($variantItem) use ($variantIds): void {
                            $variantItem
                                ->where('product_type', (new ProductVariant)->getMorphClass())
                                ->whereIn('product_id', $variantIds);
                        });
                    }
                });
            })
            ->exists();
    }

    public function alreadyReviewed(User $user, Product $product): bool
    {
        return Review::query()
            ->where('reviewrateable_type', $product->getMorphClass())
            ->where('reviewrateable_id', $product->id)
            ->where('author_type', $user->getMorphClass())
            ->where('author_id', $user->id)
            ->exists();
    }
}
