<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class KomerceConfigTest extends TestCase
{
    public function test_komerce_config_resolution_falls_back_to_general_api_key()
    {
        // Simulate missing specific keys, fallback to legacy/general KOMERCE_API_KEY
        Config::set('komerce.api_key', 'general-secret-key');
        Config::set('komerce.payment_api_key', '');
        Config::set('komerce.shipping_cost_api_key', '');
        Config::set('komerce.shipping_delivery_api_key', '');

        $this->assertEquals('general-secret-key', config('komerce.api_key'));
    }

    public function test_komerce_enabled_helper_evaluates_correctly()
    {
        Config::set('komerce.enabled', null);
        Config::set('komerce.payment_api_key', 'some-key');
        
        $this->assertTrue(komerce_enabled());

        Config::set('komerce.payment_api_key', '');
        Config::set('komerce.shipping_cost_api_key', '');
        Config::set('komerce.api_key', '');

        $this->assertFalse(komerce_enabled());
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
