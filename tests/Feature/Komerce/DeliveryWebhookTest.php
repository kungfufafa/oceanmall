<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use App\Models\OrderShipment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Shopper\Core\Enum\OrderStatus;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Tests\Support\SignsKomercePaymentCallbacks;
use Tests\TestCase;

final class DeliveryWebhookTest extends TestCase
{
    use RefreshDatabase;
    use SignsKomercePaymentCallbacks;

    public function test_delivery_webhook_updates_shipment_status_from_komerce_payload(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');

        $order = Order::factory()->create([
            'currency_code' => 'IDR',
            'status' => OrderStatus::Processing,
            'payment_status' => PaymentStatus::Paid,
        ]);
        $inventory = Inventory::factory()->create();
        $shipment = OrderShipment::query()->create([
            'order_id' => $order->id,
            'inventory_id' => $inventory->id,
            'carrier_code' => 'jne',
            'carrier_name' => 'JNE',
            'service_code' => 'REG',
            'status' => 'labeled',
            'metadata' => [
                'komerce' => [
                    'order_no' => 'RO-ORDER-WH-1',
                    'awb' => 'JNE-OLD',
                ],
            ],
        ]);

        $this->postSignedKomerceDeliveryWebhook([
            'order_no' => 'RO-ORDER-WH-1',
            'cnote' => 'JNE-NEW-AWB',
            'status' => 'Selesai',
        ])
            ->assertOk()
            ->assertJson(['status' => 'handled']);

        $shipment->refresh();
        $this->assertSame('delivered', $shipment->status);
        $this->assertSame('JNE-NEW-AWB', $shipment->awb);
        $this->assertSame('Selesai', data_get($shipment->metadata, 'komerce.webhook_status'));
    }

    public function test_delivery_webhook_returns_404_when_shipment_missing(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');

        $this->postSignedKomerceDeliveryWebhook([
            'order_no' => 'RO-MISSING',
            'cnote' => 'AWB-MISSING',
            'status' => 'Dikirim',
        ])->assertNotFound()->assertJson(['status' => 'not_found']);
    }

    public function test_delivery_webhook_rejects_unsigned_payload(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');

        $this->postJson(route('webhooks.komerce.delivery'), [
            'order_no' => 'RO-ORDER-WH-1',
            'cnote' => 'JNE-FORGED',
            'status' => 'Selesai',
        ])
            ->assertUnauthorized()
            ->assertJson(['status' => 'invalid_secret']);
    }

    public function test_delivery_webhook_rejects_invalid_hmac_signature(): void
    {
        config()->set('komerce.api_key', 'test-komerce-key');
        config()->set('komerce.webhook_secret', 'webhook-secret');

        $this->postSignedKomerceDeliveryWebhook([
            'order_no' => 'RO-ORDER-WH-1',
            'cnote' => 'JNE-FORGED',
            'status' => 'Selesai',
        ], overrideSignature: 'not-a-valid-hmac')
            ->assertUnauthorized()
            ->assertJson(['status' => 'invalid_secret']);
    }
}
