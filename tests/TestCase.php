<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Fortify\Features;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep Feature tests hermetic: local UAT credentials in `.env` must not
        // flip komerce_enabled() on and change warehouse/payment behavior.
        config([
            'komerce.enabled' => null,
            'komerce.api_key' => '',
            'komerce.payment_api_key' => '',
            'komerce.shipping_cost_api_key' => '',
            'komerce.shipping_delivery_api_key' => '',
            'komerce.qrisly_api_key' => '',
            'komerce.qrisly_qris_id' => '',
            'komerce.webhook_secret' => '',
        ]);
    }

    protected function skipUnlessFortifyHas(string $feature, ?string $message = null): void
    {
        if (! Features::enabled($feature)) {
            $this->markTestSkipped($message ?? "Fortify feature [{$feature}] is not enabled.");
        }
    }
}
