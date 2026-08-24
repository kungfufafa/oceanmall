<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class KomerceConfigTest extends TestCase
{
    public function test_legacy_general_api_key_is_not_a_service_fallback()
    {
        Config::set('komerce.api_key', 'general-secret-key');
        Config::set('komerce.payment_api_key', '');
        Config::set('komerce.shipping_cost_api_key', '');
        Config::set('komerce.shipping_delivery_api_key', '');

        $this->assertFalse(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertFalse(komerce_enabled());
    }

    public function test_komerce_enabled_helper_evaluates_correctly()
    {
        Config::set('komerce.enabled', null);
        Config::set('komerce.payment_api_key', 'some-key');
        
        $this->assertTrue(komerce_enabled());

        Config::set('komerce.payment_api_key', '');
        Config::set('komerce.shipping_cost_api_key', '');
        $this->assertFalse(komerce_enabled());
    }

    public function test_service_readiness_is_isolated_by_dedicated_key(): void
    {
        Config::set('komerce.enabled', null);
        Config::set('komerce.shipping_cost_api_key', 'cost-key');

        $this->assertTrue(komerce_shipping_cost_enabled());
        $this->assertFalse(komerce_payment_enabled());
        $this->assertFalse(komerce_shipping_delivery_enabled());
        $this->assertTrue(komerce_enabled());
    }

    public function test_qrisly_enabled_helper_evaluates_correctly()
    {
        Config::set('komerce.qrisly_api_key', 'qris-api-key');
        Config::set('komerce.qrisly_qris_id', 'qris-id-123');

        $this->assertTrue(qrisly_enabled());

        Config::set('komerce.qrisly_qris_id', '');

        $this->assertFalse(qrisly_enabled());
    }
}
