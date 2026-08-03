<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Collection;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class CollectionController extends Controller
{
    public function show(Request $request, Collection $collection): Response
    {
        abort_unless($collection->is_enabled, 404);

        $sort = (string) $request->string('sort', 'latest');

        $query = $collection->products()
            ->scopes('publish')
            ->with(['media', 'brand'])
            ->withCurrentPrices();

        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return Inertia::render('shop/collection', [
            'collection' => $collection->load('media'),
            'products' => $query->paginate(12)->withQueryString(),
            'filters' => ['sort' => $sort],
        ]);
    }
}
