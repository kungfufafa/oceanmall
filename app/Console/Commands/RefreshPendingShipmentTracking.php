<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Actions\Shipping\RefreshShipmentTracking;
use App\Models\OrderShipment;
use Illuminate\Console\Command;
use Throwable;

final class RefreshPendingShipmentTracking extends Command
{
    protected $signature = 'komerce:refresh-shipment-tracking {--limit=50 : Max shipments to refresh}';

    protected $description = 'Poll RajaOngkir tracking for shipments that are not yet delivered';

    public function handle(RefreshShipmentTracking $refresh): int
    {
        if (! komerce_shipping_delivery_enabled()) {
            $this->info('Komerce Shipping Delivery is disabled; skipping tracking refresh.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $shipments = OrderShipment::query()
            ->whereNotNull('awb')
            ->where('awb', '!=', '')
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhere('status', '!=', NormalizeShipmentStatus::DELIVERED);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $ok = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            try {
                $refresh->handle($shipment);
                $ok++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
                $this->warn("Shipment #{$shipment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Refreshed {$ok} shipment(s); {$failed} failed.");

        return self::SUCCESS;
    }
}
