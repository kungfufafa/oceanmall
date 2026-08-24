<?php

declare(strict_types=1);

namespace Tests\Unit\Shipping;

use App\Shipping\Drivers\KomerceShippingDriver;
use App\Support\KomerceTrackingContext;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\Exceptions\ShippingException;
use Shopper\Shipping\Facades\Shipping;
use Tests\TestCase;

final class KomerceShippingDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.shipping_delivery_api_key', 'test-delivery-key');
        config()->set('komerce.rajaongkir.delivery_base_url', 'https://delivery.example.test');
    }

    public function test_shopper_registers_configured_komerce_shipping_driver(): void
    {
        $this->assertContains('komerce', Shipping::availableDrivers());
        $this->assertTrue(Shipping::isConfigured('komerce'));

        $driver = Shipping::driver('komerce');

        $this->assertInstanceOf(KomerceShippingDriver::class, $driver);
        $this->assertFalse($driver->supportsRealTimeRates());
        $this->assertTrue($driver->supportsLabels());
        $this->assertTrue($driver->supportsTracking());
    }

    public function test_track_requires_courier_context_and_calls_delivery_history_endpoint(): void
    {
        Http::fake([
            'https://delivery.example.test/order/api/v1/orders/history-airway-bill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'airway_bill' => 'JNE123',
                    'last_status' => 'ON_PROCESS',
                    'history' => [
                        ['desc' => 'Paket dijemput', 'date' => '2026-08-12', 'status' => 'PICKED'],
                    ],
                ],
            ]),
        ]);

        resolve(KomerceTrackingContext::class)->setCourier('JNE');

        $info = Shipping::driver('komerce')->track('JNE123');

        $this->assertSame('JNE123', $info->trackingNumber);
        $this->assertSame('in_transit', $info->status);
        $this->assertSame('ON_PROCESS', $info->statusDescription);
        $this->assertNotEmpty($info->events);

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://delivery.example.test/order/api/v1/orders/history-airway-bill')
                && $request['shipping'] === 'JNE'
                && $request['airway_bill'] === 'JNE123';
        });
    }

    public function test_create_shipment_requires_fulfillment_context(): void
    {
        $this->expectException(ShippingException::class);

        Shipping::driver('komerce')->createShipment(
            new Address(
                firstName: 'A',
                lastName: 'B',
                street: 'Jl 1',
                city: 'Jakarta',
                postalCode: '10110',
                state: 'DKI',
                country: 'ID',
            ),
            new Address(
                firstName: 'C',
                lastName: 'D',
                street: 'Jl 2',
                city: 'Bandung',
                postalCode: '40111',
                state: 'Jabar',
                country: 'ID',
            ),
            [],
            'jne:REG',
        );
    }

    public function test_track_without_courier_throws(): void
    {
        $this->expectException(ShippingException::class);

        Shipping::driver('komerce')->track('JNE123');
    }
}
