<?php

declare(strict_types=1);

namespace Tests\Feature\Account;

use App\Models\OrderShipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\TestCase;

final class OrderShipmentTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_show_vue_source_renders_shipments_section(): void
    {
        $orderShowPage = file_get_contents(resource_path('js/pages/account/order-show.vue'));

        $this->assertIsString($orderShowPage);
        $this->assertStringContainsString('type Shipment', $orderShowPage);
        $this->assertStringContainsString('formatShipmentStatus', $orderShowPage);
        $this->assertStringContainsString('shipment.tracking_number', $orderShowPage);
        $this->assertStringContainsString('Pengiriman / Paket', $orderShowPage);
        $this->assertStringContainsString('Label menunggu', $orderShowPage);
        $this->assertStringContainsString('shipment.tracking_number', $orderShowPage);
    }

    public function test_order_show_vue_source_computes_shipping_price_from_shipments_when_present(): void
    {
        $orderShowPage = file_get_contents(resource_path('js/pages/account/order-show.vue'));

        $this->assertIsString($orderShowPage);
        // Must sum shipment costs when shipments are present
        $this->assertStringContainsString('props.shipments.length > 0', $orderShowPage);
        $this->assertStringContainsString('props.shipments.reduce', $orderShowPage);
        $this->assertStringContainsString('sum + s.cost', $orderShowPage);
        // Must fall back to shipping_option price when no shipments
        $this->assertStringContainsString('props.order.shipping_option?.price ?? 0', $orderShowPage);
    }

    public function test_customer_order_show_includes_per_shipment_tracking_details(): void
    {
        $this->withoutVite();

        $customer = User::factory()->create();
        $order = Order::factory()->create([
            'customer_id' => $customer->id,
            'currency_code' => 'IDR',
        ]);

        $jakarta = Inventory::factory()->create(['name' => 'Gudang Jakarta']);
        $cirebon = Inventory::factory()->create(['name' => 'Gudang Cirebon']);

        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $jakarta->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Regular',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'label_created',
            'awb' => 'JNE123456789',
            'tracking_number' => 'TRK-JNE-001',
        ]);

        OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $cirebon->id,
            'carrier_code' => 'jnt',
            'carrier_name' => 'J&T Express',
            'service_code' => 'EZ',
            'service_name' => 'EZ',
            'cost' => 13000,
            'currency_code' => 'IDR',
            'status' => 'pending',
            'awb' => null,
            'tracking_number' => null,
        ]);

        $this->actingAs($customer)
            ->get(route('account.orders.show', $order))
            ->assertOk()
            ->assertInertia(
                fn (Assert $page) => $page
                    ->component('account/order-show')
                    ->has('shipments', 2)
                    ->where('shipments.0.inventory_name', 'Gudang Jakarta')
                    ->where('shipments.0.status', 'label_created')
                    ->where('shipments.0.awb', 'JNE123456789')
                    ->where('shipments.0.tracking_number', 'TRK-JNE-001')
                    ->where('shipments.0.carrier', 'JNE')
                    ->where('shipments.0.service', 'Regular')
                    ->where('shipments.0.cost', 18000)
                    ->where('shipments.0.currency', 'IDR')
                    ->where('shipments.1.inventory_name', 'Gudang Cirebon')
                    ->where('shipments.1.status', 'pending')
                    ->where('shipments.1.awb', null)
                    ->where('shipments.1.tracking_number', null)
                    ->where('shipments.1.carrier', 'J&T Express')
                    ->where('shipments.1.service', 'EZ')
                    ->where('shipments.1.cost', 13000)
                    ->where('shipments.1.currency', 'IDR'),
            );
    }
}
