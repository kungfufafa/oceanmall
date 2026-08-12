<?php

declare(strict_types=1);

namespace Tests\Unit\Domain;

use App\Domain\Payment\Adapters\KomercePaymentAdapter;
use App\Domain\Payment\DTO\PaymentRequestData;
use App\Domain\Payment\Services\PaymentService;
use App\Domain\Shipping\Adapters\RajaOngkirShippingAdapter;
use App\Domain\Shipping\DTO\DeliveryOrderRequestData;
use App\Domain\Shipping\DTO\ShippingRateRequestData;
use App\Domain\Shipping\Services\ShippingService;
use App\Services\Komerce\PaymentClient;
use App\Services\Komerce\QrislyClient;
use App\Services\Komerce\ShippingCostClient;
use App\Services\Komerce\ShippingDeliveryClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use LogicException;
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
                    'payment_id' => 'TRX123',
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
            customerEmail: 'ahmad@example.com',
            customerPhone: '081234567890',
            items: [[
                'name' => 'Kopi Arabika',
                'quantity' => 1,
                'price' => 150000,
            ]],
            expiresInMinutes: 60,
        );

        $result = $service->createPayment($request);

        $this->assertEquals('TRX123', $result->transactionId);
        $this->assertEquals('TRX123', $result->paymentRef);
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
                        'service' => 'REG',
                        'description' => 'Layanan Reguler',
                        'cost' => 18000,
                        'etd' => '2-3 Hari',
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

    public function test_shipping_adapter_refuses_to_invent_incomplete_delivery_payload(): void
    {
        $adapter = new RajaOngkirShippingAdapter(new ShippingCostClient, new ShippingDeliveryClient);

        $request = new DeliveryOrderRequestData(
            shipmentId: 1,
            orderNumber: 'INV/2026/001',
            originId: 123,
            destinationSubdistrictId: 456,
            senderName: 'OceanMall',
            senderPhone: '081234567890',
            receiverName: 'Ahmad User',
            receiverPhone: '081298765432',
            receiverAddress: 'Jl. Merdeka 1',
            courier: 'jne',
            service: 'REG',
            weightInGrams: 1000,
        );

        Http::fake();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('official Store Order plus Pickup contracts');

        $adapter->createDeliveryOrder($request);
    }

    public function test_payment_adapter_verifies_webhook_signature_correctly(): void
    {
        $secret = 'secret_key_123';
        config(['komerce.webhook_secret' => $secret]);

        $payload = [
            'order_id' => 'INV/2026/001',
            'payment_reference' => 'VA888123',
            'status' => 'paid',
            'paid_at' => '2026-08-06T14:00:00Z',
        ];

        $rawBody = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $signature = \App\Support\KomerceCallbackSignature::sign($rawBody, $secret);

        $adapter = new KomercePaymentAdapter(new PaymentClient, new QrislyClient);
        $result = $adapter->verifyWebhook($payload, $signature);

        $this->assertTrue($result->isValid);
        $this->assertEquals('paid', $result->status);
        $this->assertEquals('INV/2026/001', $result->orderNumber);
        $this->assertEquals('VA888123', $result->paymentRef);
    }
}
