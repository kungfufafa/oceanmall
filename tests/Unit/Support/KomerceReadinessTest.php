<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use Tests\TestCase;

final class KomerceReadinessTest extends TestCase
{
    public function test_each_product_requires_its_own_api_key(): void
    {
        config()->set('komerce.payment_api_key', 'payment-key');

        $this->assertTrue(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertFalse(qrisly_enabled());
        $this->assertTrue(komerce_enabled());

        config()->set('komerce.payment_api_key', '');
        config()->set('komerce.shipping_cost_api_key', 'cost-key');

        $this->assertFalse(komerce_payment_enabled());
        $this->assertTrue(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertTrue(komerce_enabled());
    }

    public function test_legacy_general_key_cannot_enable_any_product(): void
    {
        config()->set('komerce.api_key', 'legacy-key');

        $this->assertFalse(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertFalse(qrisly_enabled());
        $this->assertFalse(komerce_enabled());
    }

    public function test_explicit_true_does_not_replace_required_credentials(): void
    {
        config()->set('komerce.enabled', true);

        $this->assertFalse(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertFalse(komerce_enabled());
    }

    public function test_master_switch_disables_every_ready_product(): void
    {
        config()->set('komerce.payment_api_key', 'payment-key');
        config()->set('komerce.shipping_cost_api_key', 'cost-key');
        config()->set('komerce.shipping_delivery_api_key', 'delivery-key');
        config()->set('komerce.qrisly_api_key', 'qrisly-key');
        config()->set('komerce.qrisly_qris_id', '42');
        config()->set('komerce.enabled', false);

        $this->assertFalse(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertFalse(qrisly_enabled());
        $this->assertFalse(komerce_enabled());
    }

    public function test_qrisly_requires_both_its_key_and_merchant_qris_id(): void
    {
        config()->set('komerce.qrisly_api_key', 'qrisly-key');

        $this->assertFalse(qrisly_enabled());

        config()->set('komerce.qrisly_qris_id', '42');

        $this->assertTrue(qrisly_enabled());
        $this->assertTrue(komerce_enabled());
    }
}
