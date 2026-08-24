<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Product;
use Illuminate\Support\Collection;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;

final class CustomerCatalogPresenter
{
    /**
     * @return array<string, mixed>
     */
    public function product(Product $product): array
    {
        $product->append(['thumbnail', 'images']);
        $price = $product->prices->first();
        if (! $price && $product->relationLoaded('variants')) {
            $price = $product->variants
                ->flatMap(static fn ($variant) => $variant->prices)
                ->sortBy('amount')
                ->first();
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'thumbnail' => $product->thumbnail,
            'images' => $product->images,
            'brand' => $product->brand ? [
                'id' => $product->brand->id,
                'name' => $product->brand->name,
                'slug' => $product->brand->slug,
            ] : null,
            'price' => $price?->amount,
            'compare_price' => $price?->compare_amount,
            'currency' => current_currency(),
        ];
    }

    /**
     * @param  Collection<int, Product>  $products
     * @return list<array<string, mixed>>
     */
    public function products(Collection $products): array
    {
        return $products->map(fn (Product $product): array => $this->product($product))->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function cart(Cart $cart, mixed $context): array
    {
        $cart->loadMissing(['lines.purchasable.media']);

        return [
            'id' => $cart->id,
            'currency' => $cart->currency_code,
            'coupon_code' => $cart->coupon_code,
            'lines' => $cart->lines->map(function (CartLine $line): array {
                $purchasable = $line->purchasable;
                $name = is_object($purchasable) ? (string) ($purchasable->name ?? '') : '';
                $thumbnail = null;
                if (is_object($purchasable) && method_exists($purchasable, 'getAttribute')) {
                    $thumbnail = $purchasable->thumbnail ?? null;
                }

                return [
                    'id' => $line->id,
                    'quantity' => (int) $line->quantity,
                    'unit_price' => (int) $line->unit_price_amount,
                    'name' => $name,
                    'thumbnail' => $thumbnail,
                    'purchasable_type' => $line->purchasable_type,
                    'purchasable_id' => $line->purchasable_id,
                ];
            })->values()->all(),
            'totals' => $context ? [
                'subtotal' => (int) ($context->subtotal ?? 0),
                'discount' => (int) ($context->discountTotal ?? 0),
                'tax' => (int) ($context->taxTotal ?? 0),
                'total' => (int) ($context->total ?? 0),
            ] : [
                'subtotal' => 0,
                'discount' => 0,
                'tax' => 0,
                'total' => 0,
            ],
        ];
    }
}
