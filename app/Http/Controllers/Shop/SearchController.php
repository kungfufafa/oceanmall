<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
        ]);

        $query = (string) $request->string('q', '');

        $products = mb_strlen($query) < 2
            ? null
            : Product::query()
                ->scopes('publish')
                ->where('name', 'like', '%'.str_replace(['%', '_'], ['\%', '\_'], $query).'%')
                ->with(['media', 'brand'])
                ->withCurrentPrices()
                ->paginate(12)
                ->withQueryString();

        return Inertia::render('shop/search', [
            'query' => $query,
            'products' => $products,
        ]);
    }
}
