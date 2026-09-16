<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class CancelOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_cancel_own_unpaid_order_from_account(): void
    {
        Http::fake();

        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($customer)
            ->post("/account/orders/{$order->id}/cancel")
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $order->refresh();
        $this->assertSame(OrderStatus::Cancelled, $order->status);
        $this->assertSame(PaymentStatus::Voided, $order->payment_status);

        $metadata = json_decode((string) $order->getAttribute('metadata'), true);
        $this->assertSame('Cancelled by customer', data_get($metadata, 'komerce.cancelled_reason'));
    }

    public function test_customer_cannot_cancel_another_users_order(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();

        $order = Order::factory()->create([
            'customer_id' => $owner->id,
            'status' => OrderStatus::New,
            'payment_status' => PaymentStatus::Pending,
        ]);

        $this->actingAs($intruder)
            ->post("/account/orders/{$order->id}/cancel")
            ->assertForbidden();

        $this->assertSame(OrderStatus::New, $order->fresh()->status);
    }

    public function test_paid_order_cannot_be_cancelled_from_account(): void
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);

        $this->actingAs($customer)
            ->post("/account/orders/{$order->id}/cancel")
            ->assertRedirect()
            ->assertSessionHasErrors('cancel');

        $this->assertSame(PaymentStatus::Paid, $order->fresh()->payment_status);
    }
}
