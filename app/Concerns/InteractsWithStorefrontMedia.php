<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait InteractsWithStorefrontMedia
{
    public function initializeInteractsWithStorefrontMedia(): void
    {
        $this->append(['thumbnail', 'images']);
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::get(function (): ?string {
            $url = $this->getFirstMediaUrl(
                (string) config('shopper.media.storage.thumbnail_collection', 'thumbnail'),
            );

            return $url !== '' ? $url : null;
        });
    }

    /** @return Attribute<array<int, array{id: int|string, url: string, name: ?string, extension: ?string}>, never> */
    protected function images(): Attribute
    {
        return Attribute::get(fn (): array => $this->getMedia(
            (string) config('shopper.media.storage.collection_name', 'uploads'),
        )->map(fn (Media $media): array => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'name' => $media->name,
            'extension' => $media->extension,
        ])->values()->all());
    }
}
