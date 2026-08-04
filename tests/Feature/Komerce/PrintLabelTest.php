<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Models\OrderShipment;
use App\Models\User;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

final class PrintLabelTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDeliveryConfig(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        Role::query()->firstOrCreate([
            'name' => config('shopper.admin.roles.admin'),
            'guard_name' => 'web',
        ]);
        $admin->assignRole(config('shopper.admin.roles.admin'));

        return $admin;
    }

    /**
     * @return array{0: Order, 1: OrderShipment}
     */
    private function orderWithDeliveryOrder(?string $komerceOrderNo = 'RO-ORDER-777'): array
    {
        $order = Order::factory()->create(['currency_code' => 'IDR']);
        $inventory = Inventory::factory()->create();

        $metadata = $komerceOrderNo === null
            ? null
            : ['komerce' => ['order_no' => $komerceOrderNo, 'awb' => 'JNE-AWB-777']];

        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'service_code' => 'REG',
            'status' => 'labeled',
            'awb' => 'JNE-AWB-777',
            'metadata' => $metadata,
        ]);

        return [$order, $shipment];
    }

    public function test_print_label_client_posts_order_numbers_and_page_with_api_key(): void
    {
        $this->fakeDeliveryConfig();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'message' => 'Generate Print Label Success', 'status' => 'success'],
                'data' => ['path' => 'https://delivery.example.test/storage/label/RO-1.pdf'],
            ]),
        ]);

        $response = resolve(ShippingDeliveryClient::class)->printLabel(['RO-1', 'RO-2'], 'page_5');

        $this->assertSame('https://delivery.example.test/storage/label/RO-1.pdf', data_get($response, 'data.path'));

        Http::assertSent(function (Request $request): bool {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://delivery.example.test/order/api/v1/orders/print-label')
                && $request->hasHeader('x-api-key', 'test-komerce-key')
                && ($query['order_no'] ?? null) === 'RO-1,RO-2'
                && ($query['page'] ?? null) === 'page_5';
        });
    }

    public function test_print_label_rejects_unsupported_page_format(): void
    {
        $this->fakeDeliveryConfig();
        Http::fake();

        $this->expectException(\InvalidArgumentException::class);

        resolve(ShippingDeliveryClient::class)->printLabel(['RO-1'], 'page_99');
    }

    public function test_admin_can_print_shipment_label_and_is_redirected_to_pdf(): void
    {
        $this->fakeDeliveryConfig();
        [$order] = $this->orderWithDeliveryOrder();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['path' => 'https://delivery.example.test/storage/label/RO-ORDER-777.pdf'],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.print-label', $order))
            ->assertRedirect('https://delivery.example.test/storage/label/RO-ORDER-777.pdf');

        Http::assertSent(function (Request $request): bool {
            $query = [];
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return str_starts_with($request->url(), 'https://delivery.example.test/order/api/v1/orders/print-label')
                && ($query['order_no'] ?? null) === 'RO-ORDER-777'
                && ($query['page'] ?? null) === 'page_5';
        });
    }

    public function test_admin_print_label_resolves_relative_path_against_delivery_base(): void
    {
        $this->fakeDeliveryConfig();
        [$order] = $this->orderWithDeliveryOrder();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['path' => '/storage/label-relative.pdf'],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.print-label', $order))
            ->assertRedirect('https://delivery.example.test/storage/label-relative.pdf');
    }

    public function test_admin_print_label_streams_base64_pdf_when_path_missing(): void
    {
        $this->fakeDeliveryConfig();
        [$order] = $this->orderWithDeliveryOrder();

        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/print-label*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => ['base_64' => base64_encode('%PDF-fake-label')],
            ]),
        ]);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.print-label', $order))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertSee('%PDF-fake-label', false);
    }

    public function test_print_label_requires_admin_role(): void
    {
        $this->fakeDeliveryConfig();
        [$order] = $this->orderWithDeliveryOrder();
        Http::fake();

        $this->actingAs(User::factory()->create())
            ->get(route('admin.orders.print-label', $order))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_print_label_without_delivery_order_returns_error(): void
    {
        $this->fakeDeliveryConfig();
        [$order] = $this->orderWithDeliveryOrder(komerceOrderNo: null);
        Http::fake();

        $this->from(route('admin.orders.show', $order))
            ->actingAs($this->admin())
            ->get(route('admin.orders.print-label', $order))
            ->assertSessionHasErrors('label');

        Http::assertNothingSent();
    }

    public function test_print_label_blocked_when_komerce_disabled(): void
    {
        config()->set('komerce.enabled', false);
        config()->set('komerce.api_key', '');
        [$order] = $this->orderWithDeliveryOrder();
        Http::fake();

        $this->from(route('admin.orders.show', $order))
            ->actingAs($this->admin())
            ->get(route('admin.orders.print-label', $order))
            ->assertSessionHasErrors('label');

        Http::assertNothingSent();
    }
}
