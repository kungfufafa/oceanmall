<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Checkout\CancelUnpaidKomerceOrder;
use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Shopper\Payment\Models\PaymentTransaction;
use Throwable;

final class ExpireUnpaidKomerceOrders extends Command
{
    protected $signature = 'komerce:expire-unpaid-orders {--limit=100 : Max orders to expire}';

    protected $description = 'Cancel unpaid Komerce orders past their payment expiry and release stock';

    public function handle(CancelUnpaidKomerceOrder $cancel): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $now = now();

        $orders = Order::query()
            ->where('payment_status', PaymentStatus::Pending)
            ->where('status', OrderStatus::New)
            ->whereHas('paymentTransactions', static function ($query): void {
                $query->where('driver', 'komerce');
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();

        $expired = 0;
        $skipped = 0;

        foreach ($orders as $order) {
            if (! $this->isExpired($order, $now)) {
                $skipped++;

                continue;
            }

            try {
                $cancel->handle($order, 'Payment expired');
                $expired++;
            } catch (Throwable $e) {
                report($e);
                $this->warn("Order #{$order->id}: {$e->getMessage()}");
            }
        }

        $this->info("Expired {$expired} order(s); skipped {$skipped}.");

        return self::SUCCESS;
    }

    private function isExpired(Order $order, CarbonInterface $now): bool
    {
        $metadata = $this->decodeMetadata($order->getAttribute('metadata'));
        $expiry = data_get($metadata, 'komerce.expiry_date');

        if (! is_string($expiry) || trim($expiry) === '') {
            $expiry = PaymentTransaction::query()
                ->where('order_id', $order->id)
                ->where('driver', 'komerce')
                ->latest('id')
                ->value('metadata->expiry_date');
        }

        if (is_string($expiry) && trim($expiry) !== '') {
            try {
                return \Carbon\Carbon::parse($expiry)->lte($now);
            } catch (Throwable) {
                // Fall through to created_at fallback.
            }
        }

        return $order->created_at !== null
            && $order->created_at->copy()->addDay()->lte($now);
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeMetadata(mixed $metadata): array
    {
        if (is_array($metadata)) {
            return $metadata;
        }

        if (! is_string($metadata) || trim($metadata) === '') {
            return [];
        }

        $decoded = json_decode($metadata, true);

        return is_array($decoded) ? $decoded : [];
    }
}
