<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Product\BuildVariantOptions;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

final class ProductController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Product::query()
            ->scopes('publish')
            ->with(['media', 'brand'])
            ->withCurrentPrices();

        $search = (string) $request->string('search', '');

        if ($search !== '') {
            $escaped = str_replace(['%', '_'], ['\%', '\_'], $search);
            $query->where('name', 'like', "%{$escaped}%");
        }

        if ($categoryId = $request->integer('category')) {
            $query->whereHas('categories', fn ($q) => $q->where('id', $categoryId));
        }

        $sort = (string) $request->string('sort', 'latest');
        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            default => $query->latest(),
        };

        return Inertia::render('shop/index', [
            'products' => $query->paginate(12)->withQueryString(),
            'categories' => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
                'category' => $categoryId,
                'sort' => $sort,
            ],
        ]);
    }

    public function show(Product $product): Response
    {
        abort_unless($product->isPublished(), 404);

        $currencyCode = current_currency();
        $priceConstraint = fn ($q) => $q->whereRelation('currency', 'code', $currencyCode);

        $product->load([
            'brand',
            'media',
            'prices' => $priceConstraint,
            'relatedProducts.brand',
            'relatedProducts.media',
            'relatedProducts.prices' => $priceConstraint,
            'variants.media',
            'variants.values.attribute',
            'variants.prices' => $priceConstraint,
        ]);

        $variantOptions = null;

        if ($product->canUseVariants() && $product->variants->isNotEmpty()) {
            ProductVariant::loadCurrentStock($product->variants); // @phpstan-ignore argument.type
            $product->variants->each(fn (ProductVariant $variant) => $variant->append('stock'));
            $variantOptions = resolve(BuildVariantOptions::class)->handle($product);
        } else {
            $product->setAttribute('real_stock', $product->getStock());
            $product->append('stock');
        }

        if (filled($product->description)) {
            $product->setAttribute(
                'description',
                str($product->description)->sanitizeHtml()->toString(),
            );
        }

        return Inertia::render('shop/product', [
            'product' => $product,
            'variantOptions' => $variantOptions,
        ]);
    }
}
