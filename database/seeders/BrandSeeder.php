<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Support\Facades\DB;

class BrandSeeder extends AbstractSeeder
{
    public function run(): void
    {
        $brands = $this->getSeedData('brands');

        $thumbnailCollection = config('shopper.media.storage.thumbnail_collection', 'thumbnail');

        $this->command->warn(PHP_EOL.'Creating brands...');

        DB::transaction(function () use ($brands, $thumbnailCollection): void {
            foreach ($brands as $brand) {
                $brandModel = Brand::query()->updateOrCreate(
                    ['slug' => $brand->slug],
                    [
                        'name' => $brand->name,
                        'description' => $brand->description,
                        'website' => $brand->website,
                        'is_enabled' => $brand->is_enabled,
                        'position' => $brand->position,
                    ],
                );

                if ($brand->image) {
                    $this->syncSeedMedia($brandModel, 'brands', $brand->image, $thumbnailCollection);
                }
            }
        });

        $this->command->info('Brands created successfully.');
    }
}
