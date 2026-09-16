<?php

declare(strict_types=1);

namespace Tests\Unit\Actions\Account;

use App\Actions\Account\CancelOrderByCustomer;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class CancelOrderByCustomerTest extends TestCase
{
    public function test_cancelled_reason_is_null_unless_order_is_cancelled(): void
    {
        $order = new Order([
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'metadata' => json_encode([
                'komerce' => ['cancelled_reason' => 'Payment expired'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertNull(CancelOrderByCustomer::cancelledReason($order));
        $this->assertNull(CancelOrderByCustomer::cancelledReasonLabel($order));
    }

    public function test_cancelled_reason_label_matches_storefront_and_shopper_wording(): void
    {
        $expired = new Order([
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Voided,
            'metadata' => json_encode([
                'komerce' => ['cancelled_reason' => 'Payment expired'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertSame('Payment expired', CancelOrderByCustomer::cancelledReason($expired));
        $this->assertSame(
            'Pesanan dibatalkan otomatis karena pembayaran kedaluwarsa.',
            CancelOrderByCustomer::cancelledReasonLabel($expired),
        );
        $this->assertSame(
            'Pesanan dibatalkan otomatis karena pembayaran kedaluwarsa.',
            CancelOrderByCustomer::cancelledReasonLabel($expired, 'admin'),
        );

        $customer = new Order([
            'status' => OrderStatus::Cancelled,
            'payment_status' => PaymentStatus::Voided,
            'metadata' => json_encode([
                'komerce' => ['cancelled_reason' => 'Cancelled by customer'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $this->assertSame(
            'Pesanan dibatalkan oleh Anda.',
            CancelOrderByCustomer::cancelledReasonLabel($customer),
        );
        $this->assertSame(
            'Pesanan dibatalkan oleh pelanggan.',
            CancelOrderByCustomer::cancelledReasonLabel($customer, 'admin'),
        );
    }
}
