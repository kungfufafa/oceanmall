<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Shipping;

use App\Actions\Shipping\NormalizeShipmentStatus;
use Tests\TestCase;

final class NormalizeShipmentStatusTest extends TestCase
{
    public function test_maps_common_carrier_statuses(): void
    {
        $normalize = new NormalizeShipmentStatus;

        $this->assertSame('delivered', $normalize->handle('DELIVERED'));
        $this->assertSame('delivered', $normalize->handle('Package delivered'));
        $this->assertSame('delivered', $normalize->handle('Selesai'));
        $this->assertSame('in_transit', $normalize->handle('ON_PROCESS'));
        $this->assertSame('in_transit', $normalize->handle('Dikirim'));
        $this->assertSame('picked_up', $normalize->handle('PICKED_UP'));
        $this->assertSame('picked_up', $normalize->handle('Dijemput'));
        $this->assertSame('labeled', $normalize->handle('labeled'));
        $this->assertSame('labeled', $normalize->handle('Diajukan'));
        $this->assertSame('pending', $normalize->handle('pending'));
        $this->assertSame('labeled', $normalize->handle(null, 'labeled'));
    }
}
