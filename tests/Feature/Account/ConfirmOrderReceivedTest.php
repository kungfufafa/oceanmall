<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Actions\Shipping\NormalizeShipmentStatus;
use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Enum\ShippingStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class ConfirmOrderReceivedTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Order, 2: OrderShipment}
     */
    private function paidOrderWithLabeledShipment(): array
    {
        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'shipping_status' => ShippingStatus::Shipped,
            'currency_code' => 'IDR',
        ]);
        $inventory = Inventory::factory()->create();
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Regular',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => NormalizeShipmentStatus::IN_TRANSIT,
            'awb' => 'JNE123',
            'tracking_number' => 'JNE123',
        ]);

        return [$customer, $order, $shipment];
    }

    public function test_customer_can_confirm_order_received(): void
    {
        [$customer, $order, $shipment] = $this->paidOrderWithLabeledShipment();

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.confirm-received', $order))
            ->assertRedirect(route('account.orders.show', $order));

        $order->refresh();
        $shipment->refresh();

        $this->assertSame(NormalizeShipmentStatus::DELIVERED, $shipment->status);
        $this->assertSame(ShippingStatus::Delivered, $order->shipping_status);
        $this->assertSame(OrderStatus::Completed, $order->status);
    }

    public function test_confirm_received_is_idempotent_when_already_completed(): void
    {
        [$customer, $order] = $this->paidOrderWithLabeledShipment();
        $order->forceFill([
            'status' => OrderStatus::Completed,
            'shipping_status' => ShippingStatus::Delivered,
        ])->save();

        $this->actingAs($customer)
            ->post(route('account.orders.confirm-received', $order))
            ->assertRedirect();

        $this->assertSame(OrderStatus::Completed, $order->fresh()->status);
    }

    public function test_confirm_received_requires_payment_and_awb(): void
    {
        [$customer, $order, $shipment] = $this->paidOrderWithLabeledShipment();
        $order->forceFill(['payment_status' => PaymentStatus::Pending])->save();

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.confirm-received', $order))
            ->assertSessionHasErrors('received');

        $shipment->forceFill(['awb' => null, 'tracking_number' => null])->save();
        $order->forceFill(['payment_status' => PaymentStatus::Paid])->save();

        $this->actingAs($customer)
            ->from(route('account.orders.show', $order))
            ->post(route('account.orders.confirm-received', $order))
            ->assertSessionHasErrors('received');
    }

    public function test_non_owner_cannot_confirm_received(): void
    {
        [, $order] = $this->paidOrderWithLabeledShipment();
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('account.orders.confirm-received', $order))
            ->assertForbidden();
    }
}
