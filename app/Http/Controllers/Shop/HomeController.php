<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Inertia\Inertia;
use Inertia\Response;

final class HomeController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('shop/home', [
            'featuredProducts' => fn () => Product::query()
                ->select('id', 'name', 'slug', 'brand_id')
                ->with(['media', 'brand'])
                ->withCurrentPrices()
                ->where('featured', true)
                ->scopes('publish')
                ->limit(8)
                ->get(),
            'featuredCollections' => fn () => Collection::query()
                ->has('products')
                ->withCount('products')
                ->with('media')
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
        ]);
    }
}
