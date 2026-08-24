<?php

declare(strict_types=1);

namespace Tests\Feature\Shop;

use App\Actions\Checkout\CancelUnpaidKomerceOrder;
use App\Enums\OrderNotificationType;
use App\Models\User;
use App\Notifications\OrderStatusNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $manifest = public_path('build/manifest.json');
        $this->withHeader(
            'X-Inertia-Version',
            file_exists($manifest) ? hash_file('xxh128', $manifest) : '',
        );
    }

    public function test_customer_can_view_and_mark_notifications_read(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'number' => 'ORD-1001',
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'currency_code' => 'IDR',
        ]);

        $customer->notifyNow(new OrderStatusNotification($order, OrderNotificationType::AwaitingPayment));

        $this->assertSame(1, $customer->unreadNotifications()->count());

        $this->actingAs($customer)
            ->get(route('account.notifications'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('account/notifications')
                ->has('notifications.data', 1)
                ->where('notificationsUnreadCount', 1)
            );

        $id = $customer->notifications()->firstOrFail()->id;

        $this->actingAs($customer)
            ->post(route('account.notifications.read', $id))
            ->assertRedirect();

        $this->assertSame(0, $customer->fresh()->unreadNotifications()->count());
    }

    public function test_cancel_unpaid_notifies_customer(): void
    {
        Notification::fake();

        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
            'shipping_status' => ShippingStatus::Unfulfilled,
            'currency_code' => 'IDR',
            'metadata' => json_encode(['komerce' => ['provider' => 'qrisly']], JSON_THROW_ON_ERROR),
        ]);

        resolve(CancelUnpaidKomerceOrder::class)->handle($order);

        Notification::assertSentTo(
            $customer,
            OrderStatusNotification::class,
            function (OrderStatusNotification $notification) use ($customer): bool {
                return ($notification->toDatabase($customer)['type'] ?? null)
                    === OrderNotificationType::Cancelled->value;
            },
        );
    }
}
