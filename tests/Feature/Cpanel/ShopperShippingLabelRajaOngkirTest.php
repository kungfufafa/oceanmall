<?php

declare(strict_types=1);

namespace Tests\Feature\Cpanel;

use App\Livewire\Shopper\OrderFulfillment;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderAddress;
use Shopper\Core\Models\OrderItem;
use Shopper\Core\Models\OrderShipping;
use Shopper\Payment\Enum\TransactionStatus;
use Shopper\Payment\Enum\TransactionType;
use Shopper\Payment\Models\PaymentTransaction;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class ShopperShippingLabelRajaOngkirTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        $this->configureShopperCpanel();

        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        return $admin;
    }

    private function fakeDelivery(): void
    {
        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
        config()->set('komerce.pickup_time', '10:00:00');
        config()->set('komerce.pickup_vehicle', 'Motor');
    }

    public function test_shopper_fulfillment_issues_rajaongkir_awb_instead_of_crud_form(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();
        [$order] = $this->paidOrderWithShipment();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/store' => Http::response([
                'meta' => ['message' => 'Success Create New Order', 'code' => 201, 'status' => 'success'],
                'data' => ['order_id' => 31001, 'order_no' => 'RO-SHOPPER-1'],
            ]),
            'https://delivery.example.test/order/api/v1/pickup/request' => Http::response([
                'meta' => ['message' => 'Success Request Pickup', 'code' => 201, 'status' => 'success'],
                'data' => [[
                    'status' => 'success',
                    'order_no' => 'RO-SHOPPER-1',
                    'awb' => 'JNE-SHOPPER-1',
                ]],
            ]),
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['path' => 'https://delivery.example.test/storage/label/RO-SHOPPER-1.pdf'],
            ]),
        ]);

        Livewire::actingAs($admin)
            ->test(OrderFulfillment::class, ['order' => $order])
            ->assertSee('Terbitkan Resi RajaOngkir', false)
            ->assertDontSee('e.g. 1Z999AA10123456784', false)
            ->call('openShippingLabel')
            ->assertRedirect(route('shopper.orders.fulfillment.print-label', $order));

        $order->refresh();
        $shipment = OrderShipment::query()->where('order_id', $order->id)->first();

        $this->assertNotNull($shipment);
        $this->assertSame('JNE-SHOPPER-1', $shipment->awb);
        $this->assertDatabaseHas((new OrderShipping)->getTable(), [
            'order_id' => $order->id,
            'tracking_number' => 'JNE-SHOPPER-1',
        ]);
    }

    public function test_shopper_fulfillment_does_not_issue_label_before_payment(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();
        [$order] = $this->paidOrderWithShipment();
        $order->update(['payment_status' => PaymentStatus::Pending]);

        Http::fake();

        Livewire::actingAs($admin)
            ->test(OrderFulfillment::class, ['order' => $order])
            ->assertSee('Menunggu pelunasan', false)
            ->call('openShippingLabel')
            ->assertNoRedirect();

        $this->assertNull(OrderShipment::query()->where('order_id', $order->id)->value('awb'));
        Http::assertNothingSent();
    }

    public function test_shopper_shipments_page_does_not_offer_manual_tracking_crud_when_delivery_is_on(): void
    {
        $this->fakeDelivery();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->get(route('shopper.orders.shipments'))
            ->assertOk()
            ->assertDontSee('e.g. 1Z999AA10123456784', false)
            ->assertDontSee('https://your-tracking-url.com', false);
    }

    /**
     * @return array{0: Order, 1: OrderShipment}
     */
    private function paidOrderWithShipment(): array
    {
        $address = OrderAddress::query()->create([
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Merdeka 1',
            'postal_code' => '10110',
            'city' => 'Jakarta',
            'phone' => '081234567890',
        ]);

        $order = Order::factory()->create([
            'number' => 'ORD-LABEL-1',
            'price_amount' => 100000,
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
            'shipping_address_id' => $address->id,
            'metadata' => json_encode([
                'komerce' => ['payment_type' => 'bank_transfer'],
                'shipping_address' => ['rajaongkir_destination_id' => '152'],
            ], JSON_THROW_ON_ERROR),
        ]);

        $inventory = Inventory::factory()->create([
            'name' => 'Gudang Cirebon',
            'email' => 'gudang@oceanmall.test',
            'phone_number' => '02311234567',
            'street_address' => 'Jl. Gudang 10',
            'city' => 'Cirebon',
            'postal_code' => '45111',
            'rajaongkir_origin_id' => '501',
        ]);

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'REG',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'pending',
            'metadata' => [
                'rate' => [
                    'provider' => 'shipping_cost',
                    'shipping_name' => 'JNE',
                    'service_name' => 'REG',
                    'shipping_cost' => 18000,
                    'amount' => 18000,
                    'carrier_code' => 'jne',
                ],
            ],
        ]);

        $product = Product::factory()->standard()->create([
            'name' => 'Kopi Cirebon',
            'sku' => 'KOPI-1',
            'weight_value' => 500,
            'weight_unit' => 'g',
            'width_value' => 12,
            'width_unit' => 'cm',
            'height_value' => 8,
            'height_unit' => 'cm',
            'depth_value' => 20,
            'depth_unit' => 'cm',
        ]);

        OrderItem::query()->create([
            'order_id' => $order->id,
            'name' => $product->name,
            'sku' => $product->sku,
            'product_type' => $product->getMorphClass(),
            'product_id' => $product->id,
            'quantity' => 1,
            'unit_price_amount' => 100000,
        ]);

        $shipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 1,
        ]);

        PaymentTransaction::query()->create([
            'order_id' => $order->id,
            'driver' => 'komerce',
            'type' => TransactionType::Initiate,
            'status' => TransactionStatus::Pending,
            'amount' => 100000,
            'currency_code' => 'IDR',
            'reference' => 'KOMPAY-LABEL-1',
        ]);

        return [$order, $shipment];
    }
}
