<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CategoryController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('shop/categories', [
            'categories' => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->with('media')
                ->withCount(['products' => fn ($q) => $q->whereNull(shopper_table('products').'.deleted_at')])
                ->orderBy('position')
                ->get(),
        ]);
    }

    public function show(Request $request, Category $category): Response
    {
        $sort = (string) $request->string('sort', 'latest');

        $query = Product::query()
            ->scopes('publish')
            ->whereHas('categories', fn ($q) => $q->where('id', $category->id))
            ->with(['media', 'brand'])
            ->withCurrentPrices();

        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return Inertia::render('shop/category', [
            'category' => $category->load('media'),
            'products' => $query->paginate(12)->withQueryString(),
            'filters' => ['sort' => $sort],
        ]);
    }
}
