<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shop;

use App\Actions\Product\BuildVariantOptions;
use App\Actions\Product\CustomerPurchasedProduct;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Price;

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

        if ($brandId = $request->integer('brand')) {
            $query->where('brand_id', $brandId);
        }

        $sort = (string) $request->string('sort', 'latest');
        $allowedSorts = ['latest', 'name', 'price_asc', 'price_desc'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'latest';
        }

        $currencyId = Currency::query()
            ->where('code', current_currency())
            ->value('id');
        $priceableType = (new Product)->getMorphClass();

        $priceSubquery = fn () => Price::query()
            ->select('amount')
            ->whereColumn('priceable_id', shopper_table('products').'.id')
            ->where('priceable_type', $priceableType)
            ->when(
                $currencyId,
                fn ($q) => $q->where('currency_id', $currencyId),
            )
            ->limit(1);

        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            'price_asc' => $query->orderBy($priceSubquery())->orderBy('name'),
            'price_desc' => $query->orderByDesc($priceSubquery())->orderBy('name'),
            default => $query->latest(),
        };

        return Inertia::render('shop/index', [
            'products' => $query->paginate(20)->withQueryString(),
            'categories' => Category::query()
                ->scopes('enabled')
                ->whereNull('parent_id')
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'brands' => Brand::query()
                ->scopes('enabled')
                ->whereHas('products', fn ($q) => $q->scopes('publish'))
                ->orderBy('position')
                ->get(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
                'category' => $categoryId ?: null,
                'brand' => $brandId ?: null,
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

        $approvedQuery = $product->ratings()->where('approved', true);
        $totalCount = (clone $approvedQuery)->count();
        $reviews = (clone $approvedQuery)
            ->with('author')
            ->orderByDesc('rating')
            ->latest()
            ->limit(20)
            ->get()
            ->map(static fn ($review): array => [
                'id' => $review->id,
                'rating' => (int) $review->rating,
                'title' => $review->title,
                'content' => $review->content,
                'is_recommended' => (bool) $review->is_recommended,
                'created_at' => $review->created_at?->toIso8601String(),
                'author_name' => trim(implode(' ', array_filter([
                    $review->author?->first_name ?? null,
                    $review->author?->last_name ?? null,
                ]))) ?: ($review->author?->email ?? 'Pembeli'),
            ]);

        $breakdown = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
        if ($totalCount > 0) {
            $counts = (clone $approvedQuery)
                ->selectRaw('rating, COUNT(*) as aggregate')
                ->groupBy('rating')
                ->pluck('aggregate', 'rating');

            foreach ($counts as $rating => $count) {
                $key = (int) $rating;
                if (isset($breakdown[$key])) {
                    $breakdown[$key] = (int) $count;
                }
            }
        }

        $user = request()->user();
        $purchased = resolve(CustomerPurchasedProduct::class);
        $canReview = $user !== null
            && $purchased->handle($user, $product)
            && ! $purchased->alreadyReviewed($user, $product);

        return Inertia::render('shop/product', [
            'product' => $product,
            'variantOptions' => $variantOptions,
            'reviews' => [
                'items' => $reviews,
                'averageRating' => $totalCount > 0
                    ? round((float) (clone $approvedQuery)->avg('rating'), 1)
                    : 0.0,
                'totalCount' => $totalCount,
                'breakdown' => $breakdown,
            ],
            'canReview' => $canReview,
        ]);
    }
}
