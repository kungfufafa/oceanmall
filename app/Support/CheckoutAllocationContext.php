<?php

declare(strict_types=1);

namespace App\Support;

use App\DTO\AllocationPlan;

final class CheckoutAllocationContext
{
    private ?AllocationPlan $plan = null;

    public function set(AllocationPlan $plan): void
    {
        $this->plan = $plan;
    }

    public function get(): ?AllocationPlan
    {
        return $this->plan;
    }

    public function clear(): void
    {
        $this->plan = null;
    }
}
