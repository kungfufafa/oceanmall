<?php

declare(strict_types=1);

namespace Tests\Feature\Checkout;

use App\Actions\CreateOrder;
use App\CheckoutSession;
use App\Models\OrderShipment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Shopper\Cart\Models\Cart;
use Shopper\Cart\Models\CartLine;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\PaymentMethod;
use Tests\TestCase;

final class SplitShipmentOrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_order_persists_split_shipments_from_allocation_plan(): void
    {
        $this->assertTrue(Schema::hasTable('order_shipments'));
        $this->assertTrue(Schema::hasTable('order_shipment_lines'));

        $user = User::factory()->create();
        $paymentMethod = PaymentMethod::factory()->create(['driver' => 'manual', 'is_enabled' => true]);
        $defaultInventory = Inventory::factory()->create(['is_default' => true]);
        $secondaryInventory = Inventory::factory()->create(['is_default' => false]);
        $product = Product::factory()->standard()->create(['name' => 'Split Stock Product']);

        $product->mutateStock($defaultInventory->id, 2);
        $product->mutateStock($secondaryInventory->id, 3);

        $cart = Cart::query()->create([
            'currency_code' => 'IDR',
            'customer_id' => $user->id,
        ]);

        CartLine::query()->create([
            'cart_id' => $cart->id,
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'quantity' => 4,
            'unit_price_amount' => 100000,
        ]);

        $this->actingAs($user);
        session()->put(config('shopper.cart.session.key', 'shopper_cart'), $cart->id);
        session()->put(CheckoutSession::KEY, [
            'shipping_address' => [
                'first_name' => 'Budi',
                'last_name' => 'Santoso',
                'street_address' => 'Jl. Merdeka 1',
                'postal_code' => '10110',
                'city' => 'Jakarta',
                'country_id' => $defaultInventory->country_id,
                'phone_number' => '081234567890',
            ],
            'shipping_option' => [[
                'id' => 'split-shipment',
                'name' => 'Split shipment',
                'price' => 0,
            ]],
            'shipping_options_by_shipment' => [
                $defaultInventory->id => [
                    'id' => 'jne:REG',
                    'carrier_code' => 'jne',
                    'carrier_name' => 'Jalur Nugraha Ekakurir (JNE)',
                    'service_code' => 'REG',
                    'service_name' => 'Reguler',
                    'amount' => 12000,
                    'currency' => 'IDR',
                ],
                $secondaryInventory->id => [
                    'id' => 'jnt:EZ',
                    'carrier_code' => 'jnt',
                    'carrier_name' => 'J&T Express',
                    'service_code' => 'EZ',
                    'service_name' => 'Regular Service',
                    'amount' => 18000,
                    'currency' => 'IDR',
                ],
            ],
            'payment' => [[
                'id' => $paymentMethod->id,
                'driver' => 'manual',
                'title' => 'Manual',
            ]],
        ]);

        $order = resolve(CreateOrder::class)->handle();

        $shipments = OrderShipment::query()
            ->with('lines')
            ->where('order_id', $order->id)
            ->orderBy('id')
            ->get();

        $this->assertSame(430000, $order->refresh()->price_amount);
        $this->assertCount(2, $shipments);

        $this->assertSame($defaultInventory->id, $shipments[0]->inventory_id);
        $this->assertSame('jne', $shipments[0]->carrier_code);
        $this->assertSame('Jalur Nugraha Ekakurir (JNE)', $shipments[0]->carrier_name);
        $this->assertSame('REG', $shipments[0]->service_code);
        $this->assertSame('Reguler', $shipments[0]->service_name);
        $this->assertSame(12000, $shipments[0]->cost);
        $this->assertSame('pending', $shipments[0]->status);
        $this->assertSame([
            [
                'purchasable_type' => $product->getMorphClass(),
                'purchasable_id' => $product->id,
                'qty' => 2,
            ],
        ], $shipments[0]->lines->map->only(['purchasable_type', 'purchasable_id', 'qty'])->all());

        $this->assertSame($secondaryInventory->id, $shipments[1]->inventory_id);
        $this->assertSame('jnt', $shipments[1]->carrier_code);
        $this->assertSame('EZ', $shipments[1]->service_code);
        $this->assertSame(18000, $shipments[1]->cost);
        $this->assertSame([
            [
                'purchasable_type' => $product->getMorphClass(),
                'purchasable_id' => $product->id,
                'qty' => 2,
            ],
        ], $shipments[1]->lines->map->only(['purchasable_type', 'purchasable_id', 'qty'])->all());
    }
}
