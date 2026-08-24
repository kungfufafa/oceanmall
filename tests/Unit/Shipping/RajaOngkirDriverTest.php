<?php

declare(strict_types=1);

namespace Tests\Unit\Shipping;

use App\Shipping\Drivers\RajaOngkirDriver;
use App\Support\KomerceTrackingContext;
use App\Support\RajaOngkirQuoteContext;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Http;
use Shopper\Shipping\DataTransferObjects\Address;
use Shopper\Shipping\DataTransferObjects\Package;
use Shopper\Shipping\DataTransferObjects\ShippingRate;
use Shopper\Shipping\Facades\Shipping;
use Tests\TestCase;

final class RajaOngkirDriverTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('komerce.shipping_cost_api_key', 'test-cost-key');
        config()->set('komerce.rajaongkir.cost_base_url', 'https://shipping.example.test');
        config()->set('komerce.couriers', ['jne', 'jnt']);
    }

    public function test_shopper_registers_configured_rajaongkir_driver(): void
    {
        $this->assertContains('rajaongkir', Shipping::availableDrivers());
        $this->assertTrue(Shipping::isConfigured('rajaongkir'));

        $driver = Shipping::driver('rajaongkir');

        $this->assertInstanceOf(RajaOngkirDriver::class, $driver);
        $this->assertTrue($driver->supportsRealTimeRates());
        $this->assertFalse($driver->supportsLabels());
        $this->assertTrue($driver->supportsTracking());
    }

    public function test_calculate_rates_posts_official_cost_payload_and_maps_flat_v2_rows(): void
    {
        Http::fake([
            'https://shipping.example.test/api/v1/calculate/domestic-cost' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    [
                        'name' => 'Jalur Nugraha Ekakurir (JNE)',
                        'code' => 'jne',
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 18000,
                        'etd' => '2-3',
                    ],
                    [
                        'name' => 'J&T Express',
                        'code' => 'jnt',
                        'service' => 'EZ',
                        'cost' => 16000,
                        'etd' => '3',
                    ],
                ],
            ]),
        ]);

        resolve(RajaOngkirQuoteContext::class)->set('501', '114', ['jne', 'jnt']);

        $rates = Shipping::driver('rajaongkir')->calculateRates(
            $this->address(),
            $this->address(),
            [new Package(length: 10, width: 10, height: 10, weight: 0.15)],
        );

        $this->assertCount(2, $rates);
        $this->assertInstanceOf(ShippingRate::class, $rates->first());
        $this->assertSame('jne:REG', $rates[0]->serviceCode);
        $this->assertSame('REG', $rates[0]->serviceName);
        $this->assertSame(18000, $rates[0]->amount);
        $this->assertSame('IDR', $rates[0]->currency);
        $this->assertSame('jne', $rates[0]->carrierCode);
        $this->assertSame('2-3', $rates[0]->estimatedDays);
        $this->assertSame('jnt:EZ', $rates[1]->serviceCode);

        Http::assertSent(function (ClientRequest $request): bool {
            return $request->method() === 'POST'
                && $request->url() === 'https://shipping.example.test/api/v1/calculate/domestic-cost'
                && $request->hasHeader('key', 'test-cost-key')
                && (string) $request['origin'] === '501'
                && (string) $request['destination'] === '114'
                && (int) $request['weight'] === 150
                && $request['courier'] === 'jne:jnt';
        });
    }

    public function test_calculate_rates_without_quote_context_returns_empty_collection(): void
    {
        Http::fake();

        $rates = Shipping::driver('rajaongkir')->calculateRates(
            $this->address(),
            $this->address(),
            [new Package(length: 10, width: 10, height: 10, weight: 1)],
        );

        $this->assertTrue($rates->isEmpty());
        Http::assertNothingSent();
    }

    public function test_track_posts_official_cost_waybill_query(): void
    {
        Http::fake([
            'https://shipping.example.test/api/v1/track/waybill*' => Http::response([
                'meta' => ['code' => 200, 'status' => 'success'],
                'data' => [
                    'delivered' => true,
                    'summary' => ['waybill_number' => 'JNE123', 'courier_code' => 'jne'],
                    'delivery_status' => ['status' => 'DELIVERED'],
                    'manifest' => [
                        [
                            'manifest_code' => '200',
                            'manifest_description' => 'Package delivered to recipient',
                            'manifest_date' => '2026-08-03',
                            'manifest_time' => '14:30',
                            'city_name' => 'Jakarta',
                        ],
                    ],
                ],
            ]),
        ]);

        resolve(KomerceTrackingContext::class)->setCourier('jne');

        $info = Shipping::driver('rajaongkir')->track('JNE123');

        $this->assertSame('JNE123', $info->trackingNumber);
        $this->assertSame('delivered', $info->status);
        $this->assertNotEmpty($info->events);

        Http::assertSent(function (ClientRequest $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'POST'
                && str_starts_with($request->url(), 'https://shipping.example.test/api/v1/track/waybill')
                && $request->hasHeader('key', 'test-cost-key')
                && ($query['awb'] ?? null) === 'JNE123'
                && ($query['courier'] ?? null) === 'jne';
        });
    }

    private function address(): Address
    {
        return new Address(
            firstName: 'Budi',
            lastName: 'Santoso',
            street: 'Jl. Merdeka 1',
            city: 'Jakarta',
            postalCode: '10110',
            state: 'DKI Jakarta',
            country: 'ID',
        );
    }
}
