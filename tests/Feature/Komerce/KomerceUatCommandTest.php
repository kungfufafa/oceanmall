<?php

declare(strict_types=1);

namespace Tests\Feature\Komerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Http;
use Shopper\Core\Models\Inventory;
use Tests\TestCase;

final class KomerceUatCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMockingConsoleOutput();
    }

    public function test_uat_command_fails_closed_when_production_gates_are_missing(): void
    {
        Http::fake([
            '*' => Http::response(['meta' => ['code' => 401]], 401),
        ]);

        $payload = $this->runUat();

        $this->assertSame(1, $payload['exit']);
        $this->assertFalse($payload['json']['production_ready']);
        $this->assertSame([
            'live_sandbox_pay_to_awb',
            'production_keys_and_webhook',
            'production_database_and_backup',
            'queue_worker_and_scheduler',
            'inventory_rajaongkir_origins',
            'volume_monitoring_oncall',
        ], array_column($payload['json']['gates'], 'id'));
        $this->assertContains($this->gate($payload, 'live_sandbox_pay_to_awb')['status'], ['FAIL', 'BLOCKED']);
        $this->assertSame('FAIL', $this->gate($payload, 'production_keys_and_webhook')['status']);
        $this->assertSame('FAIL', $this->gate($payload, 'production_database_and_backup')['status']);
        $this->assertSame('FAIL', $this->gate($payload, 'queue_worker_and_scheduler')['status']);
        $this->assertSame('FAIL', $this->gate($payload, 'volume_monitoring_oncall')['status']);
    }

    public function test_uat_command_does_not_print_secrets(): void
    {
        config()->set('komerce.payment_api_key', 'super-secret-uat-key-xyz');
        config()->set('komerce.shipping_cost_api_key', 'super-secret-cost-key-xyz');
        config()->set('komerce.shipping_delivery_api_key', 'super-secret-delivery-key-xyz');
        config()->set('komerce.webhook_secret', 'super-secret-webhook-xyz');

        Http::fake([
            '*' => Http::response(['meta' => ['code' => 401]], 401),
        ]);

        $payload = $this->runUat();

        $this->assertSame(1, $payload['exit']);
        $this->assertStringNotContainsString('super-secret-uat-key-xyz', $payload['raw']);
        $this->assertStringNotContainsString('super-secret-cost-key-xyz', $payload['raw']);
        $this->assertStringNotContainsString('super-secret-delivery-key-xyz', $payload['raw']);
        $this->assertStringNotContainsString('super-secret-webhook-xyz', $payload['raw']);
    }

    public function test_origins_are_partial_when_every_location_has_an_id_but_cost_is_offline(): void
    {
        $inventory = Inventory::factory()->create([
            'name' => 'OceanMall Cirebon',
            'city' => 'Cirebon',
        ]);
        $inventory->setAttribute('rajaongkir_origin_id', '17248');
        $inventory->save();

        Http::fake([
            '*' => Http::response(['meta' => ['code' => 401]], 401),
        ]);

        $payload = $this->runUat();
        $origins = $this->gate($payload, 'inventory_rajaongkir_origins');

        $this->assertSame(1, $payload['exit']);
        $this->assertSame('PARTIAL', $origins['status']);
        $this->assertStringContainsString('17248', $origins['detail']);
    }

    public function test_skip_live_probe_does_not_call_http(): void
    {
        Http::fake();

        $exit = Artisan::call('komerce:uat', [
            '--json' => true,
            '--skip-live-probe' => true,
        ]);

        $this->assertSame(1, $exit);
        Http::assertNothingSent();
    }

    /**
     * @return array{exit: int, raw: string, json: array<string, mixed>}
     */
    private function runUat(): array
    {
        $exit = Artisan::call('komerce:uat', ['--json' => true]);
        $raw = Artisan::output();
        $json = json_decode($raw, true);

        $this->assertIsArray($json, $raw);

        return [
            'exit' => $exit,
            'raw' => $raw,
            'json' => $json,
        ];
    }

    /**
     * @param  array{json: array<string, mixed>}  $payload
     * @return array{id: string, syarat: string, status: string, detail: string}
     */
    private function gate(array $payload, string $id): array
    {
        foreach ($payload['json']['gates'] as $gate) {
            if (($gate['id'] ?? null) === $id) {
                return $gate;
            }
        }

        $this->fail('Missing UAT gate '.$id);
    }
}
