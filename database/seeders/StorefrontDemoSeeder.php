<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Collection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Shopper\Core\Enum\CollectionType;
use Shopper\Core\Enum\ProductType;
use Shopper\Core\Models\Channel;
use Shopper\Core\Models\Currency;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Price;
use Shopper\Core\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Demo catalog so the OceanMall storefront has a complete visual picture:
 * brands, categories, collections, products (with prices, promo, stock, thumbnails).
 *
 * Safe to re-run (upsert by slug). Requires Shopper base data (currency, channel, inventory).
 *
 *   php artisan db:seed --class=StorefrontDemoSeeder
 */
final class StorefrontDemoSeeder extends Seeder
{
    public function run(): void
    {
        $idr = Currency::query()->where('code', 'IDR')->firstOrFail();
        $this->ensureDefaultCurrency($idr);

        $currency = Currency::query()->where('code', shopper_currency())->first() ?? $idr;
        $channel = Channel::query()->where('is_default', true)->first()
            ?? Channel::query()->firstOrFail();
        $inventory = Inventory::query()->firstOrFail();

        $this->command?->info('Seeding storefront demo catalog…');
        $this->command?->info("Store currency: {$currency->code}");

        $brands = $this->seedBrands();
        $categories = $this->seedCategories();
        $collections = $this->seedCollections();
        $products = $this->seedProducts(
            $brands,
            $categories,
            $currency,
            $channel,
            $inventory,
        );

        $this->attachCollections($collections, $products);

        $this->command?->info(sprintf(
            'Done: %d brands, %d categories, %d collections, %d products.',
            count($brands),
            count($categories),
            count($collections),
            count($products),
        ));
    }

    private function ensureDefaultCurrency(Currency $idr): void
    {
        Setting::query()->updateOrCreate(
            ['key' => 'default_currency_id'],
            [
                'display_name' => Setting::lockedAttributesDisplayName('default_currency_id'),
                'value' => $idr->id,
                'locked' => false,
            ],
        );

        Cache::forget('shopper-setting.default_currency_id');
        Cache::forget('shopper-setting.default_currency');
    }

    /** @return array<string, Brand> */
    private function seedBrands(): array
    {
        $items = [
            ['name' => 'Samsung', 'website' => 'https://www.samsung.com', 'position' => 1],
            ['name' => 'Apple', 'website' => 'https://www.apple.com', 'position' => 2],
            ['name' => 'Xiaomi', 'website' => 'https://www.mi.com', 'position' => 3],
            ['name' => 'OPPO', 'website' => 'https://www.oppo.com', 'position' => 4],
            ['name' => 'Garmin', 'website' => 'https://www.garmin.com', 'position' => 5],
            ['name' => 'ASUS', 'website' => 'https://www.asus.com', 'position' => 6],
        ];

        $brands = [];

        foreach ($items as $item) {
            $slug = Str::slug($item['name']);
            $brand = Brand::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $item['name'],
                    'website' => $item['website'],
                    'description' => "Produk resmi {$item['name']} di OceanMall.",
                    'is_enabled' => true,
                    'position' => $item['position'],
                ],
            );

            $this->attachThumbnail(
                $brand,
                $this->placeholderUrl($item['name'], '0A2A6B', 'FFFFFF', 400, 400),
            );

            $brands[$slug] = $brand;
        }

        return $brands;
    }

    /** @return array<string, Category> */
    private function seedCategories(): array
    {
        $items = [
            ['name' => 'Smartphone', 'position' => 1, 'color' => 'E11D48'],
            ['name' => 'Aksesoris HP', 'position' => 2, 'color' => '0284C7'],
            ['name' => 'Tablet', 'position' => 3, 'color' => '0D9488'],
            ['name' => 'Wearable', 'position' => 4, 'color' => '7C3AED'],
            ['name' => 'Laptop', 'position' => 5, 'color' => 'D97706'],
            ['name' => 'Audio', 'position' => 6, 'color' => '4F46E5'],
        ];

        $categories = [];

        foreach ($items as $item) {
            $slug = Str::slug($item['name']);
            $category = Category::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $item['name'],
                    'description' => "Kategori {$item['name']} untuk kebutuhan gadget kamu.",
                    'is_enabled' => true,
                    'position' => $item['position'],
                    'parent_id' => null,
                ],
            );

            $this->attachThumbnail(
                $category,
                $this->placeholderUrl($item['name'], $item['color'], 'FFFFFF', 400, 400),
            );

            $categories[$slug] = $category;
        }

        return $categories;
    }

    /** @return array<string, Collection> */
    private function seedCollections(): array
    {
        $items = [
            [
                'name' => 'Pre-order Samsung',
                'slug' => 'pre-order-samsung',
                'description' => 'Pre-order flagship Samsung terbaru dengan bonus menarik.',
            ],
            [
                'name' => 'Promo Gadget',
                'slug' => 'promo-gadget',
                'description' => 'Diskon terbatas untuk gadget pilihan minggu ini.',
            ],
            [
                'name' => 'Lifestyle Tech',
                'slug' => 'lifestyle-tech',
                'description' => 'Wearable, audio, dan aksesoris untuk gaya hidup digital.',
            ],
        ];

        $collections = [];

        foreach ($items as $item) {
            $collection = Collection::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'type' => CollectionType::Manual,
                    'published_at' => now()->subDay(),
                ],
            );

            $this->attachThumbnail(
                $collection,
                $this->placeholderUrl($item['name'], '0B5FFF', 'FFFFFF', 1200, 600),
            );

            $collections[$item['slug']] = $collection;
        }

        return $collections;
    }

    /**
     * @param  array<string, Brand>  $brands
     * @param  array<string, Category>  $categories
     * @return array<string, Product>
     */
    private function seedProducts(
        array $brands,
        array $categories,
        Currency $currency,
        Channel $channel,
        Inventory $inventory,
    ): array {
        $catalog = [
            [
                'name' => 'Samsung Galaxy S25',
                'slug' => 'samsung-galaxy-s25',
                'brand' => 'samsung',
                'category' => 'smartphone',
                'collections' => ['pre-order-samsung', 'promo-gadget'],
                'amount' => 14_999_000,
                'compare' => 16_999_000,
                'featured' => true,
            ],
            [
                'name' => 'Samsung Galaxy Z Flip6',
                'slug' => 'samsung-galaxy-z-flip6',
                'brand' => 'samsung',
                'category' => 'smartphone',
                'collections' => ['pre-order-samsung'],
                'amount' => 16_499_000,
                'compare' => 17_999_000,
                'featured' => true,
            ],
            [
                'name' => 'Samsung Galaxy Watch7',
                'slug' => 'samsung-galaxy-watch7',
                'brand' => 'samsung',
                'category' => 'wearable',
                'collections' => ['lifestyle-tech', 'promo-gadget'],
                'amount' => 4_299_000,
                'compare' => 4_999_000,
                'featured' => false,
            ],
            [
                'name' => 'iPhone 16',
                'slug' => 'iphone-16',
                'brand' => 'apple',
                'category' => 'smartphone',
                'collections' => ['promo-gadget'],
                'amount' => 15_999_000,
                'compare' => null,
                'featured' => true,
            ],
            [
                'name' => 'iPad Air M2',
                'slug' => 'ipad-air-m2',
                'brand' => 'apple',
                'category' => 'tablet',
                'collections' => ['lifestyle-tech'],
                'amount' => 12_499_000,
                'compare' => 13_499_000,
                'featured' => true,
            ],
            [
                'name' => 'AirPods Pro 2',
                'slug' => 'airpods-pro-2',
                'brand' => 'apple',
                'category' => 'audio',
                'collections' => ['lifestyle-tech', 'promo-gadget'],
                'amount' => 3_799_000,
                'compare' => 4_299_000,
                'featured' => false,
            ],
            [
                'name' => 'Xiaomi 14T',
                'slug' => 'xiaomi-14t',
                'brand' => 'xiaomi',
                'category' => 'smartphone',
                'collections' => ['promo-gadget'],
                'amount' => 6_499_000,
                'compare' => 7_499_000,
                'featured' => true,
            ],
            [
                'name' => 'Redmi Buds 5 Pro',
                'slug' => 'redmi-buds-5-pro',
                'brand' => 'xiaomi',
                'category' => 'audio',
                'collections' => ['lifestyle-tech'],
                'amount' => 899_000,
                'compare' => 1_099_000,
                'featured' => false,
            ],
            [
                'name' => 'OPPO Find X8',
                'slug' => 'oppo-find-x8',
                'brand' => 'oppo',
                'category' => 'smartphone',
                'collections' => ['promo-gadget'],
                'amount' => 12_999_000,
                'compare' => 13_999_000,
                'featured' => true,
            ],
            [
                'name' => 'OPPO Enco X3',
                'slug' => 'oppo-enco-x3',
                'brand' => 'oppo',
                'category' => 'audio',
                'collections' => ['lifestyle-tech'],
                'amount' => 1_799_000,
                'compare' => null,
                'featured' => false,
            ],
            [
                'name' => 'Garmin Forerunner 165',
                'slug' => 'garmin-forerunner-165',
                'brand' => 'garmin',
                'category' => 'wearable',
                'collections' => ['lifestyle-tech', 'promo-gadget'],
                'amount' => 4_599_000,
                'compare' => 5_199_000,
                'featured' => true,
            ],
            [
                'name' => 'Garmin Venu 3',
                'slug' => 'garmin-venu-3',
                'brand' => 'garmin',
                'category' => 'wearable',
                'collections' => ['lifestyle-tech'],
                'amount' => 7_999_000,
                'compare' => null,
                'featured' => false,
            ],
            [
                'name' => 'ASUS Vivobook 14',
                'slug' => 'asus-vivobook-14',
                'brand' => 'asus',
                'category' => 'laptop',
                'collections' => ['promo-gadget'],
                'amount' => 8_999_000,
                'compare' => 9_999_000,
                'featured' => true,
            ],
            [
                'name' => 'ASUS ROG Ally',
                'slug' => 'asus-rog-ally',
                'brand' => 'asus',
                'category' => 'laptop',
                'collections' => ['lifestyle-tech'],
                'amount' => 11_499_000,
                'compare' => 12_499_000,
                'featured' => false,
            ],
            [
                'name' => 'Kabel USB-C 2m',
                'slug' => 'kabel-usb-c-2m',
                'brand' => 'samsung',
                'category' => 'aksesoris-hp',
                'collections' => ['lifestyle-tech'],
                'amount' => 149_000,
                'compare' => 199_000,
                'featured' => false,
            ],
            [
                'name' => 'Power Bank 20000mAh',
                'slug' => 'power-bank-20000mah',
                'brand' => 'xiaomi',
                'category' => 'aksesoris-hp',
                'collections' => ['promo-gadget', 'lifestyle-tech'],
                'amount' => 349_000,
                'compare' => 449_000,
                'featured' => false,
            ],
        ];

        $products = [];

        foreach ($catalog as $item) {
            $brand = $brands[$item['brand']] ?? null;
            $category = $categories[$item['category']] ?? null;

            $product = Product::query()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'sku' => 'OM-'.Str::upper(Str::substr(md5($item['slug']), 0, 8)),
                    'description' => "<p>{$item['name']} resmi di OceanMall. Garansi resmi, stok ready.</p>",
                    'summary' => "{$item['name']} — original OceanMall.",
                    'featured' => $item['featured'],
                    'is_visible' => true,
                    'type' => ProductType::Standard,
                    'published_at' => now()->subDays(3),
                    'brand_id' => $brand?->id,
                    'weight_value' => 350,
                    'weight_unit' => 'g',
                    'security_stock' => 5,
                    'allow_backorder' => false,
                ],
            );

            Price::query()->updateOrCreate(
                [
                    'priceable_type' => $product->getMorphClass(),
                    'priceable_id' => $product->id,
                    'currency_id' => $currency->id,
                ],
                [
                    'amount' => $item['amount'],
                    'compare_amount' => $item['compare'],
                    'cost_amount' => (int) round($item['amount'] * 0.75),
                ],
            );

            $product->channels()->syncWithoutDetaching([$channel->id]);

            if ($category) {
                $product->categories()->syncWithoutDetaching([$category->id]);
            }

            if ($product->stockInventory($inventory->id) < 10) {
                $product->mutateStock($inventory->id, 30);
            }

            $this->attachThumbnail(
                $product,
                $this->placeholderUrl($item['name'], 'F4F6FA', '0A2A6B', 800, 800),
            );

            $products[$item['slug']] = $product->setAttribute('_collection_slugs', $item['collections']);
        }

        return $products;
    }

    /**
     * @param  array<string, Collection>  $collections
     * @param  array<string, Product>  $products
     */
    private function attachCollections(array $collections, array $products): void
    {
        $map = [];

        foreach ($products as $product) {
            /** @var list<string> $slugs */
            $slugs = $product->getAttribute('_collection_slugs') ?? [];

            foreach ($slugs as $slug) {
                $map[$slug][] = $product->id;
            }
        }

        foreach ($map as $slug => $ids) {
            $collection = $collections[$slug] ?? null;

            if (! $collection) {
                continue;
            }

            $collection->products()->syncWithoutDetaching($ids);
        }
    }

    private function placeholderUrl(
        string $text,
        string $bg,
        string $fg,
        int $width,
        int $height,
    ): string {
        $label = rawurlencode(Str::limit($text, 28, ''));

        return "https://placehold.co/{$width}x{$height}/{$bg}/{$fg}/png?text={$label}";
    }

    private function attachThumbnail(Brand|Category|Collection|Product $model, string $url): void
    {
        $collection = (string) config('shopper.media.storage.thumbnail_collection', 'thumbnail');

        if ($model->getFirstMediaUrl($collection) !== '') {
            return;
        }

        try {
            $model
                ->addMediaFromUrl($url)
                ->usingName((string) ($model->name ?? 'thumbnail'))
                ->toMediaCollection($collection);
        } catch (Throwable $e) {
            $this->command?->warn("Skip media for {$model->name}: {$e->getMessage()}");
        }
    }
}
