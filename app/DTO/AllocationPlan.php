<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class AllocationPlan
{
    /**
     * @param  list<ShipmentDraft>  $shipments
     */
    public function __construct(
        public array $shipments,
    ) {}
}
