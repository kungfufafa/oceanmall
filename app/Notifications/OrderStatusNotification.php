<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\OrderNotificationType;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Shopper\Core\Models\Order;

final class OrderStatusNotification extends Notification
{
    public function __construct(
        private readonly Order $order,
        private readonly OrderNotificationType $type,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array{title: string, body: string, order_id: int, order_number: string, url: string, type: string}
     */
    public function toDatabase(object $notifiable): array
    {
        $number = (string) $this->order->number;

        return [
            'title' => $this->type->title(),
            'body' => $this->type->body($number),
            'order_id' => (int) $this->order->id,
            'order_number' => $number,
            'url' => route('account.orders.show', $this->order),
            'type' => $this->type->value,
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $number = (string) $this->order->number;

        return (new MailMessage)
            ->subject($this->type->title().' — '.$number)
            ->greeting('Halo,')
            ->line($this->type->body($number))
            ->action('Lihat pesanan', route('account.orders.show', $this->order))
            ->line('Terima kasih telah belanja di OceanMall.');
    }
}
