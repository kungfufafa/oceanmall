<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Models\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Core\Enum\CollectionType;
use Tests\TestCase;

final class CollectionShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_published_collection_page_is_ok(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Promo Gadget',
            'slug' => 'promo-gadget',
            'type' => CollectionType::Manual,
            'published_at' => now()->subDay(),
        ]);

        $this->get(route('shop.collection', $collection))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('shop/collection')
                ->where('collection.slug', 'promo-gadget'));
    }

    public function test_unpublished_collection_returns_404(): void
    {
        $collection = Collection::query()->create([
            'name' => 'Draft Collection',
            'slug' => 'draft-collection',
            'type' => CollectionType::Manual,
            'published_at' => now()->addWeek(),
        ]);

        $this->get(route('shop.collection', $collection))->assertNotFound();
    }
}
