<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\OrderShipmentOpsPresenter;
use App\Support\ShipmentStatusLabel;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class ShipmentStatusLabelTest extends TestCase
{
    #[DataProvider('shopperStatusLabels')]
    public function test_labels_match_shopper_ops_presenter(?string $status, string $label): void
    {
        $ops = new OrderShipmentOpsPresenter();

        $this->assertSame($label, $ops->statusLabel($status));
        $this->assertSame($label, ShipmentStatusLabel::for($status));
    }

    /**
     * @return array<string, array{0: ?string, 1: string}>
     */
    public static function shopperStatusLabels(): array
    {
        return [
            'pending' => ['pending', 'Menunggu resi'],
            'ready' => ['ready', 'Menunggu resi'],
            'labeled' => ['labeled', 'Resi terbit'],
            'picked_up' => ['picked_up', 'Sudah dijemput'],
            'in_transit' => ['in_transit', 'Dalam pengiriman'],
            'delivered' => ['delivered', 'Terkirim'],
            'unknown raw' => ['label_created', 'Label created'],
            'missing' => [null, 'Unknown'],
        ];
    }
}
