<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ShipmentDraft
{
    /**
     * @param  list<array{purchasable_type: string, purchasable_id: int, qty: int}>  $lines
     */
    public function __construct(
        public int $inventory_id,
        public array $lines,
    ) {}
}
