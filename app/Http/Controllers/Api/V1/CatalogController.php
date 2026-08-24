<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Product\AddProductReviewAction;
use App\Actions\Product\BuildVariantOptions;
use App\Actions\Product\CustomerPurchasedProduct;
use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use App\Support\CustomerCatalogPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Price;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly CustomerCatalogPresenter $presenter,
    ) {}

    public function home(): JsonResponse
    {
        $currency = current_currency();

        $featured = Product::query()
            ->select('id', 'name', 'slug', 'brand_id')
            ->with(['media', 'brand'])
            ->withCurrentPrices()
            ->where('featured', true)
            ->scopes('publish')
            ->limit(10)
            ->get();

        $collections = Collection::query()
            ->scopes('published')
            ->has('products')
            ->withCount('products')
            ->with('media')
            ->orderByDesc('products_count')
            ->limit(6)
            ->get()
            ->map(fn (Collection $collection): array => [
                'id' => $collection->id,
                'name' => $collection->name,
                'slug' => $collection->slug,
                'thumbnail' => $collection->thumbnail ?? null,
                'products_count' => (int) $collection->products_count,
            ]);

        return response()->json([
            'data' => [
                'featured_products' => $this->presenter->products($featured),
                'collections' => $collections->values()->all(),
                'currency' => $currency,
            ],
        ]);
    }

    public function products(Request $request): JsonResponse
    {
        $query = Product::query()
            ->scopes('publish')
            ->with(['media', 'brand'])
            ->withCurrentPrices();

        $search = trim((string) $request->string('search', ''));
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
        $currencyId = Currency::query()->where('code', current_currency())->value('id');
        $priceableType = (new Product)->getMorphClass();
        $priceSubquery = fn () => Price::query()
            ->select('amount')
            ->whereColumn('priceable_id', shopper_table('products').'.id')
            ->where('priceable_type', $priceableType)
            ->when($currencyId, fn ($q) => $q->where('currency_id', $currencyId))
            ->limit(1);

        $query = match ($sort) {
            'name' => $query->orderBy('name'),
            'price_asc' => $query->orderBy($priceSubquery())->orderBy('name'),
            'price_desc' => $query->orderByDesc($priceSubquery())->orderBy('name'),
            default => $query->latest(),
        };

        $page = $query->paginate(20);

        return response()->json([
            'data' => $this->presenter->products($page->getCollection()),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function product(string $slug): JsonResponse
    {
        $product = Product::query()
            ->scopes('publish')
            ->with(['media', 'brand', 'variants.media'])
            ->withCurrentPrices()
            ->where('slug', $slug)
            ->firstOrFail();

        $payload = $this->presenter->product($product);
        $payload['description'] = $product->description;
        $payload['variants'] = $product->variants->map(static function ($variant): array {
            return [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'name' => $variant->name,
                'price' => $variant->prices->first()?->amount,
            ];
        })->values()->all();

        try {
            $payload['options'] = resolve(BuildVariantOptions::class)->handle($product);
        } catch (\Throwable) {
            $payload['options'] = null;
        }

        $payload['reviews'] = $this->reviewsPayload($product);

        return response()->json(['data' => $payload]);
    }

    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['nullable', 'string', 'min:2', 'max:100'],
        ]);

        $query = trim((string) $request->string('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json([
                'data' => [],
                'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 20, 'total' => 0],
            ]);
        }

        $request->merge(['search' => $query]);

        return $this->products($request);
    }

    public function category(Request $request, string $slug): JsonResponse
    {
        $category = Category::query()->scopes('enabled')->where('slug', $slug)->firstOrFail();
        $request->merge(['category' => $category->id]);

        $response = $this->products($request);
        $payload = $response->getData(true);
        $payload['category'] = [
            'id' => $category->id,
            'name' => $category->name,
            'slug' => $category->slug,
        ];

        return response()->json($payload);
    }

    public function collection(Request $request, string $slug): JsonResponse
    {
        $collection = Collection::query()
            ->scopes('published')
            ->where('slug', $slug)
            ->firstOrFail();

        $sort = (string) $request->string('sort', 'latest');
        $query = $collection->products()
            ->scopes('publish')
            ->with(['media', 'brand'])
            ->withCurrentPrices();
        $query = $sort === 'name' ? $query->orderBy('name') : $query->latest();
        $page = $query->paginate(20);

        return response()->json([
            'data' => [
                'collection' => [
                    'id' => $collection->id,
                    'name' => $collection->name,
                    'slug' => $collection->slug,
                    'thumbnail' => $collection->thumbnail ?? null,
                ],
                'products' => $this->presenter->products($page->getCollection()),
            ],
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    public function storeReview(Request $request, string $slug): JsonResponse
    {
        $product = Product::query()->scopes('publish')->where('slug', $slug)->firstOrFail();
        $data = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:120'],
            'content' => ['nullable', 'string', 'max:2000'],
            'is_recommended' => ['sometimes', 'boolean'],
        ]);

        $user = $request->user();
        $purchased = resolve(CustomerPurchasedProduct::class);
        if (! $purchased->handle($user, $product)) {
            return response()->json([
                'message' => 'Hanya pembeli yang sudah menerima pesanan yang dapat menulis ulasan.',
            ], 422);
        }
        if ($purchased->alreadyReviewed($user, $product)) {
            return response()->json(['message' => 'Kamu sudah menulis ulasan untuk produk ini.'], 422);
        }

        resolve(AddProductReviewAction::class)->execute($product, [
            'rating' => (int) $data['rating'],
            'title' => $data['title'] ?? null,
            'content' => $data['content'] ?? null,
            'is_recommended' => (bool) ($data['is_recommended'] ?? true),
        ], $user);

        return response()->json([
            'message' => 'Ulasan terkirim dan menunggu moderasi.',
        ], 201);
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewsPayload(Product $product): array
    {
        $approvedQuery = $product->ratings()->where('approved', true);
        $items = (clone $approvedQuery)
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
                ]))) ?: 'Pembeli',
            ])
            ->values()
            ->all();

        return [
            'average' => round((float) (clone $approvedQuery)->avg('rating'), 1),
            'count' => (clone $approvedQuery)->count(),
            'items' => $items,
        ];
    }

    public function categories(): JsonResponse
    {
        $categories = Category::query()
            ->scopes('enabled')
            ->whereNull('parent_id')
            ->orderBy('position')
            ->get(['id', 'name', 'slug']);

        $brands = Brand::query()
            ->scopes('enabled')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        return response()->json([
            'data' => [
                'categories' => $categories,
                'brands' => $brands,
            ],
        ]);
    }
}
