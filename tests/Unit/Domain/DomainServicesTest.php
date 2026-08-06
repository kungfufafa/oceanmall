<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Payment\Adapters\KomercePaymentAdapter;
use App\Domain\Payment\DTO\PaymentRequestData;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Shipping\Adapters\RajaOngkirShippingAdapter;
use App\Domain\Shipping\DTO\ShippingRateRequestData;
use App\Domain\Shipping\Services\ShippingService;
use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\QrislyClient;
use App\Services\Komerce\ShippingCostClient;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DomainServicesTest extends TestCase
{
    public function test_payment_service_creates_va_payment_via_adapter(): void
    {
        config(['komerce.enabled' => true, 'komerce.payment_api_key' => 'test_key']);

        Http::fake([
            '*/api/v1/user/payment/create' => Http::response([
                'status' => 'success',
                'data' => [
                    'id' => 'TRX123',
                    'payment_reference' => 'VA888123',
                    'va_number' => '88812345',
                    'bank_code' => 'bca',
                    'amount' => 150000,
                    'expired_at' => '2026-08-07T12:00:00Z',
                ],
            ], 200),
        ]);

        $adapter = new KomercePaymentAdapter(new PaymentClient, new QrislyClient);
        $service = new PaymentService($adapter);

        $request = new PaymentRequestData(
            orderId: '1',
            orderNumber: 'INV/2026/001',
            amount: 150000,
            paymentType: 'bank_transfer',
            channelCode: 'bca',
            customerName: 'Ahmad User',
            customerEmail: 'ahmad@example.com'
        );

        $result = $service->createPayment($request);

        $this->assertEquals('TRX123', $result->transactionId);
        $this->assertEquals('VA888123', $result->paymentRef);
        $this->assertEquals('88812345', $result->vaNumber);
        $this->assertEquals('bca', $result->bankName);
        $this->assertEquals(150000, $result->amount);
    }

    public function test_shipping_service_calculates_rajaongkir_rates(): void
    {
        config(['komerce.enabled' => true, 'komerce.shipping_cost_api_key' => 'test_key']);

        Http::fake([
            '*/api/v1/calculate/domestic-cost' => Http::response([
                'status' => 'success',
                'data' => [
                    [
                        'code' => 'jne',
                        'name' => 'JNE Express',
                        'costs' => [
                            [
                                'service' => 'REG',
                                'description' => 'Layanan Reguler',
                                'cost' => [
                                    ['value' => 18000, 'etd' => '2-3 Hari'],
                                ],
                            ],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $adapter = new RajaOngkirShippingAdapter(new ShippingCostClient, new ShippingDeliveryClient);
        $service = new ShippingService($adapter);

        $request = new ShippingRateRequestData(
            originId: '123',
            destinationSubdistrictId: '456',
            weightInGrams: 1000,
            couriers: ['jne']
        );

        $rates = $service->getDeliveryRates($request);

        $this->assertInstanceOf(Collection::class, $rates);
        $this->assertCount(1, $rates);
        $this->assertEquals('jne', $rates->first()->courierCode);
        $this->assertEquals('REG', $rates->first()->serviceCode);
        $this->assertEquals(18000, $rates->first()->cost);
    }
}
