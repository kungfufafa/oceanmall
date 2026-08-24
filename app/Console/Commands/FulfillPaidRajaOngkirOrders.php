<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use Illuminate\Console\Command;
use Shopper\Core\Enum\PaymentStatus;
use Throwable;

final class FulfillPaidRajaOngkirOrders extends Command
{
    protected $signature = 'komerce:fulfill-paid-orders {--limit=50 : Max unlabeled paid shipments to fulfill}';

    protected $description = 'Create RajaOngkir AWB for paid orders that still have no airway bill';

    public function handle(): int
    {
        if (! komerce_shipping_delivery_enabled()) {
            $this->info('Komerce Shipping Delivery is disabled; skipping paid-order fulfillment.');

            return self::SUCCESS;
        }

        $limit = max(1, (int) $this->option('limit'));

        $shipments = OrderShipment::query()
            ->whereHas('order', static function ($query): void {
                $query->where('payment_status', PaymentStatus::Paid);
            })
            ->where(function ($query): void {
                $query->whereNull('awb')->orWhere('awb', '');
            })
            ->where(function ($query): void {
                $query->whereNull('tracking_number')->orWhere('tracking_number', '');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $ok = 0;
        $failed = 0;

        foreach ($shipments as $shipment) {
            try {
                (new CreateRajaOngkirDeliveryForShipment((int) $shipment->id))->handle();
                $ok++;
            } catch (Throwable $e) {
                report($e);
                $failed++;
                $this->warn("Shipment #{$shipment->id}: {$e->getMessage()}");
            }
        }

        $this->info("Fulfilled {$ok} paid shipment(s); {$failed} failed.");

        return self::SUCCESS;
    }
}
