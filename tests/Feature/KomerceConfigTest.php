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

    public function test_operator_docs_list_the_same_unset_env_courier_fallback(): void
    {
        $config = (string) file_get_contents(base_path('config/komerce.php'));

        $this->assertSame(1, preg_match(
            "/env\\('RAJAONGKIR_COURIERS',\\s*'([^']+)'\\)/",
            $config,
            $matches,
        ));

        $fallback = $matches[1];
        $this->assertNotSame('', $fallback);

        $envExample = (string) file_get_contents(base_path('.env.example'));
        $readme = (string) file_get_contents(base_path('README.md'));

        $this->assertStringContainsString(
            'RAJAONGKIR_COURIERS='.$fallback,
            $envExample,
            '.env.example must list the same courier fallback as config/komerce.php so copied envs match unset-env shops',
        );
        $this->assertStringContainsString(
            $fallback,
            $readme,
            'README must document the same RAJAONGKIR_COURIERS default as config/komerce.php',
        );
    }

    public function test_operator_docs_list_every_scheduled_komerce_command(): void
    {
        $console = (string) file_get_contents(base_path('routes/console.php'));
        $readme = (string) file_get_contents(base_path('README.md'));

        preg_match_all(
            "/Schedule::command\\('([^']+)'\\)/",
            $console,
            $matches,
        );

        $commands = array_values(array_filter(
            $matches[1] ?? [],
            static fn (string $command): bool => str_starts_with($command, 'komerce:'),
        ));

        $this->assertSame(
            [
                'komerce:fulfill-paid-orders',
                'komerce:refresh-shipment-tracking',
                'komerce:expire-unpaid-orders',
            ],
            $commands,
            'Lock the living-path schedule so README cannot drift from routes/console.php',
        );

        foreach ($commands as $command) {
            $this->assertStringContainsString(
                $command,
                $readme,
                'README ops must name '.$command.' or operators following README will never run that job',
            );
        }
    }
}
