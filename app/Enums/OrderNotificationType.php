<?php

declare(strict_types=1);

namespace App\Enums;

enum OrderNotificationType: string
{
    case AwaitingPayment = 'awaiting_payment';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function title(): string
    {
        return match ($this) {
            self::AwaitingPayment => 'Menunggu pembayaran',
            self::Paid => 'Pembayaran diterima',
            self::Shipped => 'Pesanan dikirim',
            self::Delivered => 'Pesanan sampai',
            self::Cancelled => 'Pesanan dibatalkan',
        };
    }

    public function body(string $orderNumber): string
    {
        return match ($this) {
            self::AwaitingPayment => "Pesanan {$orderNumber} menunggu pembayaran. Selesaikan sebelum kadaluarsa.",
            self::Paid => "Pembayaran untuk pesanan {$orderNumber} sudah kami terima.",
            self::Shipped => "Pesanan {$orderNumber} sedang dalam pengiriman.",
            self::Delivered => "Pesanan {$orderNumber} sudah sampai. Silakan konfirmasi jika sudah diterima.",
            self::Cancelled => "Pesanan {$orderNumber} dibatalkan karena pembayaran tidak diselesaikan.",
        };
    }
}
