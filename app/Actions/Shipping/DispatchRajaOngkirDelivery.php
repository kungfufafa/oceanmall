<?php

declare(strict_types=1);

namespace App\Actions\Shipping;

use App\Jobs\CreateRajaOngkirDeliveryForShipment;
use App\Models\OrderShipment;
use Illuminate\Support\Facades\DB;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Throwable;

/**
 * After payment is committed, create the RajaOngkir AWB.
 *
 * HTTP webhooks run the job after the 200 response (same PHP process) so a
 * missing queue worker cannot leave a paid order without an AWB. Unique queued
 * jobs are only used as a retry fallback, never as the first attempt.
 */
final class DispatchRajaOngkirDelivery
{
    public function handle(Order $order): void
    {
        if (! komerce_shipping_delivery_enabled()) {
            return;
        }

        $run = function () use ($order): void {
            $order = $order->fresh() ?? $order;

            if ($order->payment_status !== PaymentStatus::Paid) {
                return;
            }

            resolve(EnsureOrderShipments::class)->handle($order);

            $shipmentIds = OrderShipment::query()
                ->where('order_id', $order->id)
                ->where(function ($query): void {
                    $query->whereNull('awb')->orWhere('awb', '');
                })
                ->where(function ($query): void {
                    $query->whereNull('tracking_number')->orWhere('tracking_number', '');
                })
                ->pluck('id');

            foreach ($shipmentIds as $shipmentId) {
                $this->dispatchOne((int) $shipmentId);
            }
        };

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($run);

            return;
        }

        $run();
    }

    public function dispatchOne(int $shipmentId): void
    {
        if (app()->runningUnitTests()) {
            CreateRajaOngkirDeliveryForShipment::dispatch($shipmentId);

            return;
        }

        if (app()->runningInConsole()) {
            CreateRajaOngkirDeliveryForShipment::dispatch($shipmentId);

            return;
        }

        app()->terminating(function () use ($shipmentId): void {
            try {
                (new CreateRajaOngkirDeliveryForShipment($shipmentId))->handle();
            } catch (Throwable $e) {
                report($e);
                CreateRajaOngkirDeliveryForShipment::dispatch($shipmentId);
            }
        });
    }
}
