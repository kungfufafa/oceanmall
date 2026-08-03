<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Actions\Checkout\FetchDeliveryRates;
use App\Actions\Warehouse\OverrideAllocation;
use App\Models\OrderShipment;
use App\Models\OrderShipmentLine;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Shopper\Core\Models\OrderAddress;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class OverrideAllocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_move_allocation_before_awb_and_recalculate_rates(): void
    {
        $fixture = $this->shipmentFixture();
        $rates = $this->fakeRates([
            $fixture['fromInventory']->id => [
                $this->rate('jne', 'REG', 11000),
                $this->rate('sicepat', 'BEST', 9000),
            ],
            $fixture['toInventory']->id => [
                $this->rate('jnt', 'EZ', 15000),
                $this->rate('jne', 'YES', 20000),
            ],
        ]);
        $this->app->instance(FetchDeliveryRates::class, $rates);

        resolve(OverrideAllocation::class)->handle($fixture['order'], [[
            'shipment_line_id' => $fixture['sourceLine']->id,
            'purchasable_type' => $fixture['product']->getMorphClass(),
            'purchasable_id' => $fixture['product']->id,
            'qty' => 1,
            'from_inventory_id' => $fixture['fromInventory']->id,
            'to_inventory_id' => $fixture['toInventory']->id,
        ]], $fixture['actor']);

        $sourceLine = $fixture['sourceLine']->refresh();
        $targetLine = OrderShipmentLine::query()
            ->where('order_shipment_id', $fixture['targetShipment']->id)
            ->where('purchasable_type', $fixture['product']->getMorphClass())
            ->where('purchasable_id', $fixture['product']->id)
            ->firstOrFail();

        $this->assertSame(1, $sourceLine->qty);
        $this->assertSame(2, $targetLine->qty);
        $this->assertSame(226000, $fixture['order']->refresh()->price_amount);
        $this->assertSame([[$fixture['fromInventory']->id, 1], [$fixture['toInventory']->id, 2]], $rates->calls);
        $this->assertSame('jne', $fixture['sourceShipment']->refresh()->carrier_code);
        $this->assertSame('REG', $fixture['sourceShipment']->service_code);
        $this->assertSame(11000, $fixture['sourceShipment']->cost);
        $this->assertSame('jnt', $fixture['targetShipment']->refresh()->carrier_code);
        $this->assertSame('EZ', $fixture['targetShipment']->service_code);
        $this->assertSame(15000, $fixture['targetShipment']->cost);
        $this->assertDatabaseHas('allocation_override_logs', [
            'order_id' => $fixture['order']->id,
            'user_id' => $fixture['actor']->id,
            'from_inventory_id' => $fixture['fromInventory']->id,
            'to_inventory_id' => $fixture['toInventory']->id,
        ]);
    }

    public function test_rejects_shipments_that_already_have_awb(): void
    {
        $fixture = $this->shipmentFixture(['awb' => 'AWB-123']);

        $this->expectException(ValidationException::class);

        resolve(OverrideAllocation::class)->handle($fixture['order'], [[
            'shipment_line_id' => $fixture['sourceLine']->id,
            'qty' => 1,
            'from_inventory_id' => $fixture['fromInventory']->id,
            'to_inventory_id' => $fixture['toInventory']->id,
        ]]);
    }

    public function test_rejects_shipments_outside_pending_or_ready_status(): void
    {
        $fixture = $this->shipmentFixture(['status' => 'labeled']);

        $this->expectException(ValidationException::class);

        resolve(OverrideAllocation::class)->handle($fixture['order'], [[
            'shipment_line_id' => $fixture['sourceLine']->id,
            'qty' => 1,
            'from_inventory_id' => $fixture['fromInventory']->id,
            'to_inventory_id' => $fixture['toInventory']->id,
        ]]);
    }

    public function test_rejects_when_destination_inventory_lacks_stock(): void
    {
        $fixture = $this->shipmentFixture(toStock: 1);

        $this->expectException(ValidationException::class);

        resolve(OverrideAllocation::class)->handle($fixture['order'], [[
            'shipment_line_id' => $fixture['sourceLine']->id,
            'qty' => 1,
            'from_inventory_id' => $fixture['fromInventory']->id,
            'to_inventory_id' => $fixture['toInventory']->id,
        ]]);
    }

    public function test_override_route_requires_admin_role(): void
    {
        $fixture = $this->shipmentFixture();
        $this->app->instance(FetchDeliveryRates::class, $this->fakeRates([
            $fixture['fromInventory']->id => [$this->rate('jne', 'REG', 11000)],
            $fixture['toInventory']->id => [$this->rate('jnt', 'EZ', 15000)],
        ]));

        $this->actingAs(User::factory()->create())
            ->postJson(route('admin.orders.override-allocation', $fixture['order']), [
                'moves' => [[
                    'shipment_line_id' => $fixture['sourceLine']->id,
                    'qty' => 1,
                    'from_inventory_id' => $fixture['fromInventory']->id,
                    'to_inventory_id' => $fixture['toInventory']->id,
                ]],
            ])
            ->assertForbidden();

        $admin = User::factory()->create();
        Role::query()->create(['name' => config('shopper.admin.roles.admin'), 'guard_name' => 'web']);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        $this->actingAs($admin)
            ->postJson(route('admin.orders.override-allocation', $fixture['order']), [
                'moves' => [[
                    'shipment_line_id' => $fixture['sourceLine']->id,
                    'qty' => 1,
                    'from_inventory_id' => $fixture['fromInventory']->id,
                    'to_inventory_id' => $fixture['toInventory']->id,
                ]],
            ])
            ->assertNoContent();
    }

    /**
     * @param  array<string, mixed>  $sourceOverrides
     * @return array<string, mixed>
     */
    private function shipmentFixture(array $sourceOverrides = [], int $toStock = 5): array
    {
        $actor = User::factory()->create();
        $fromInventory = Inventory::factory()->create(['is_default' => true]);
        $toInventory = Inventory::factory()->create(['is_default' => false]);
        $product = Product::factory()->standard()->create();
        $product->mutateStock($fromInventory->id, 5);
        $product->mutateStock($toInventory->id, $toStock);

        $shippingAddress = OrderAddress::query()->create([
            'customer_id' => $actor->id,
            'first_name' => 'Budi',
            'last_name' => 'Santoso',
            'street_address' => 'Jl. Merdeka 1',
            'postal_code' => '10110',
            'city' => 'Jakarta',
            'state' => 'DKI Jakarta',
            'phone' => '081234567890',
            'country_name' => 'Indonesia',
        ]);

        $order = Order::factory()->create([
            'customer_id' => $actor->id,
            'shipping_address_id' => $shippingAddress->id,
            'price_amount' => 230000,
            'currency_code' => 'IDR',
        ]);

        $sourceShipment = OrderShipment::query()->create(array_merge([
            'order_id' => $order->id,
            'inventory_id' => $fromInventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'service_name' => 'Reguler',
            'cost' => 12000,
            'currency_code' => 'IDR',
            'status' => 'pending',
        ], $sourceOverrides));

        $targetShipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $toInventory->id,
            'carrier_code' => 'jnt',
            'carrier_name' => 'J&T Express',
            'service_code' => 'EZ',
            'service_name' => 'Regular Service',
            'cost' => 18000,
            'currency_code' => 'IDR',
            'status' => 'ready',
        ]);

        $sourceLine = $sourceShipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 2,
        ]);

        $targetShipment->lines()->create([
            'purchasable_type' => $product->getMorphClass(),
            'purchasable_id' => $product->id,
            'qty' => 1,
        ]);

        $this->assertTrue(Schema::hasTable('order_shipments'));

        return compact('actor', 'fromInventory', 'toInventory', 'product', 'order', 'sourceShipment', 'targetShipment', 'sourceLine');
    }

    /**
     * @param  array<int, list<array<string, mixed>>>  $rates
     */
    private function fakeRates(array $rates): object
    {
        return new class($rates)
        {
            /** @var list<array{0: int|null, 1: int}> */
            public array $calls = [];

            /** @param  array<int, list<array<string, mixed>>>  $rates */
            public function __construct(private readonly array $rates) {}

            /** @param  array<int, mixed>  $packages */
            public function handle(array $shippingAddress, array $packages, ?int $originInventoryId = null): array
            {
                $this->calls[] = [$originInventoryId, count($packages)];

                return $this->rates[$originInventoryId] ?? [];
            }
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function rate(string $carrier, string $service, int $amount): array
    {
        return [
            'carrier_code' => $carrier,
            'carrier_name' => strtoupper($carrier),
            'service_code' => $service,
            'service_name' => $service,
            'amount' => $amount,
            'currency' => 'IDR',
        ];
    }
}
