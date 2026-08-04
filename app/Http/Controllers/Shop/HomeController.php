<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(): Response
    {
        $currency = current_currency();

        return Inertia::render('shop/home', [
            'featuredProducts' => fn () => Product::query()
                ->select('id', 'name', 'slug', 'brand_id')
                ->with(['media', 'brand'])
                ->withCurrentPrices()
                ->where('featured', true)
                ->scopes('publish')
                ->limit(10)
                ->get(),
            'promoProducts' => fn () => Product::query()
                ->select('id', 'name', 'slug', 'brand_id')
                ->with(['media', 'brand'])
                ->withCurrentPrices()
                ->scopes('publish')
                ->whereHas(
                    'prices',
                    fn ($q) => $q
                        ->whereRelation('currency', 'code', $currency)
                        ->whereNotNull('compare_amount')
                        ->whereColumn('compare_amount', '>', 'amount'),
                )
                ->latest()
                ->limit(10)
                ->get(),
            'featuredCollections' => fn () => Collection::query()
                ->scopes('published')
                ->has('products')
                ->withCount('products')
                ->with('media')
                ->orderByRaw("CASE slug
                    WHEN 'sales-promotions' THEN 0
                    WHEN 'featured-products' THEN 1
                    WHEN 'new-arrivals' THEN 2
                    WHEN 'best-sellers' THEN 3
                    WHEN 'premium' THEN 4
                    ELSE 10
                END")
                ->orderByDesc('products_count')
                ->limit(6)
                ->get(),
            'categories' => fn () => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->with('media')
                ->orderBy('position')
                ->limit(8)
                ->get(),
            'brands' => fn () => Brand::query()
                ->scopes('enabled')
                ->whereHas('products', fn ($q) => $q->scopes('publish'))
                ->with('media')
                ->withCount(['products' => fn ($q) => $q->scopes('publish')])
                ->orderBy('position')
                ->limit(12)
                ->get(['id', 'name', 'slug']),
        ]);
    }
}
