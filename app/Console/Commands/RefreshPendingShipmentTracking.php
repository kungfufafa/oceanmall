<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Actions\Shipping\ReconcileKomerceShipment;
use App\Actions\Shipping\RefreshShipmentTracking;
use App\Models\OrderShipment;
use Illuminate\Console\Command;
use Throwable;

final class RefreshPendingShipmentTracking extends Command
{
    protected $signature = 'komerce:refresh-shipment-tracking {--limit=50 : Max shipments to refresh}';

    protected $description = 'Reconcile Komerce delivery detail then poll tracking until delivered';

    public function handle(RefreshShipmentTracking $refresh, ReconcileKomerceShipment $reconcile): int
    {
        if (! komerce_shipping_delivery_enabled()) {
            $this->info('Komerce Shipping Delivery is disabled; skipping tracking refresh.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $shipments = OrderShipment::query()
            ->where(function ($query): void {
                $query->where(function ($awb): void {
                    $awb->whereNotNull('awb')->where('awb', '!=', '');
                })->orWhere(function ($registered): void {
                    $registered->whereNotNull('metadata->komerce->order_no')
                        ->where('metadata->komerce->order_no', '!=', '');
                });
            })
            ->where(function ($query): void {
                $query->whereNull('status')
                    ->orWhereNotIn('status', [
                        NormalizeShipmentStatus::DELIVERED,
                        NormalizeShipmentStatus::CANCELLED,
                    ]);
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $ok = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            try {
                if (filled(data_get($shipment->metadata, 'komerce.order_no'))) {
                    $shipment = $reconcile->handle($shipment);
                }

                $awb = trim((string) ($shipment->awb ?: $shipment->tracking_number));
                if ($awb !== '') {
                    $refresh->handle($shipment);
                }

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
