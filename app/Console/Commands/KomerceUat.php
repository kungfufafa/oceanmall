<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\OrderShipment;
use Illuminate\Console\Command;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Shopper\Core\Enum\PaymentStatus;
use Shopper\Core\Models\Inventory;
use Shopper\Core\Models\Order;
use Throwable;

final class KomerceUat extends Command
{
    protected $signature = 'komerce:uat
                            {--json : Print machine-readable JSON instead of a table}
                            {--skip-live-probe : Do not call RajaOngkir/Komerce HTTP (CI)}';

    protected $description = 'Score production UAT gates for Komerce payment and RajaOngkir AWB';

    /**
     * @var list<array{id: string, syarat: string, status: string, detail: string}>
     */
    private array $gates = [];

    /**
     * @var list<string>
     */
    private array $secrets = [];

    public function handle(): int
    {
        $this->secrets = array_values(array_filter([
            trim((string) config('komerce.payment_api_key', '')),
            trim((string) config('komerce.shipping_cost_api_key', '')),
            trim((string) config('komerce.shipping_delivery_api_key', '')),
            trim((string) config('komerce.qrisly_api_key', '')),
            trim((string) config('komerce.webhook_secret', '')),
        ], static fn (string $value): bool => $value !== ''));

        $probes = $this->option('skip-live-probe') ? [] : $this->liveProbes();

        $this->scoreLivePayToAwb($probes);
        $this->scoreProductionKeysAndWebhook($probes);
        $this->scoreProductionDatabase();
        $this->scoreQueueAndScheduler();
        $this->scoreInventoryOrigins($probes);
        $this->scoreVolumeMonitoring();

        $ready = collect($this->gates)->every(static fn (array $gate): bool => $gate['status'] === 'PASS');

        if ($this->option('json')) {
            $this->line(json_encode([
                'production_ready' => $ready,
                'app_env' => config('app.env'),
                'app_url_host' => parse_url((string) config('app.url'), PHP_URL_HOST),
                'gates' => $this->gates,
                'probes' => $probes,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
        } else {
            $this->table(
                ['Syarat', 'Status', 'Temuan'],
                array_map(static fn (array $gate): array => [
                    $gate['syarat'],
                    $gate['status'],
                    $gate['detail'],
                ], $this->gates),
            );

            $this->newLine();
            $this->line($ready
                ? 'UAT: PASS — semua syarat produksi terpenuhi.'
                : 'UAT: FAIL — belum siap produksi massal. Isi kunci Collaborator + URL publik + DB non-SQLite + worker 24/7, lalu jalankan ulang `php artisan komerce:uat`.');
        }

        return $ready ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  list<array{label: string, http: int, ok: bool, host: string, error?: string}>  $probes
     */
    private function scoreLivePayToAwb(array $probes): void
    {
        $paymentReady = komerce_payment_enabled();
        $costReady = komerce_shipping_cost_enabled();
        $deliveryReady = komerce_shipping_delivery_enabled();
        $webhookSecret = trim((string) config('komerce.webhook_secret', '')) !== '';

        $paidWithAwb = 0;
        $pendingPayments = 0;
        if (Schema::hasTable('order_shipments') && Schema::hasTable('sh_orders')) {
            $paidWithAwb = OrderShipment::query()
                ->whereHas('order', static function ($query): void {
                    $query->where('payment_status', PaymentStatus::Paid);
                })
                ->where(function ($query): void {
                    $query->whereNotNull('awb')->where('awb', '!=', '');
                })
                ->count();
            $pendingPayments = Order::query()
                ->where('payment_status', PaymentStatus::Pending)
                ->count();
        }

        $probeOk = $this->probeOk($probes, 'payment_methods')
            && $this->probeOk($probes, 'cost_destination')
            && $this->probeOk($probes, 'delivery_destination');

        if (! $paymentReady || ! $costReady || ! $deliveryReady) {
            $this->gate(
                'live_sandbox_pay_to_awb',
                'UAT live sandbox/portal Komerce (bayar sungguhan sampai AWB kurir)',
                'BLOCKED',
                'Tidak dijalankan: payment='.$this->yn($paymentReady)
                .' cost='.$this->yn($costReady)
                .' delivery='.$this->yn($deliveryReady)
                .' webhook_secret='.$this->yn($webhookSecret)
                .'. paid+AWB='.$paidWithAwb
                .' pending_orders='.$pendingPayments
                .'. Probe: '.$this->probeSummary($probes),
            );

            return;
        }

        if ($paidWithAwb > 0 && $probeOk) {
            $this->gate(
                'live_sandbox_pay_to_awb',
                'UAT live sandbox/portal Komerce (bayar sungguhan sampai AWB kurir)',
                'PASS',
                'Ada '.$paidWithAwb.' shipment ber-AWB pada order paid; catalog/search Komerce merespons 2xx.',
            );

            return;
        }

        $this->gate(
            'live_sandbox_pay_to_awb',
            'UAT live sandbox/portal Komerce (bayar sungguhan sampai AWB kurir)',
            $probeOk ? 'FAIL' : 'BLOCKED',
            'Kunci Cost/Payment/Delivery hidup. paid+AWB='.$paidWithAwb
            .' pending_orders='.$pendingPayments
            .' webhook_secret='.$this->yn($webhookSecret)
            .'. Pembayaran Collaborator masih PENDING/belum ada; AWB kurir belum terbit. Probe: '.$this->probeSummary($probes),
        );
    }

    /**
     * @param  list<array{label: string, http: int, ok: bool, host: string, error?: string}>  $probes
     */
    private function scoreProductionKeysAndWebhook(array $probes): void
    {
        $appUrl = (string) config('app.url');
        $public = $this->isPublicHttpsUrl($appUrl);
        $paymentSandbox = $this->isSandboxUrl((string) config('komerce.payment_base_url'));
        $deliverySandbox = $this->isSandboxUrl((string) config('komerce.rajaongkir.delivery_base_url'));
        $routes = [
            'webhooks.komerce.payment' => Route::has('webhooks.komerce.payment'),
            'webhooks.komerce.delivery' => Route::has('webhooks.komerce.delivery'),
            'webhooks.komerce.qrisly' => Route::has('webhooks.komerce.qrisly'),
        ];
        $routesOk = ! in_array(false, $routes, true);
        $keys = [
            'payment' => komerce_payment_enabled(),
            'cost' => komerce_shipping_cost_enabled(),
            'delivery' => komerce_shipping_delivery_enabled(),
            'webhook_secret' => trim((string) config('komerce.webhook_secret', '')) !== '',
        ];
        $keysOk = ! in_array(false, $keys, true);

        $webhookUrls = $public ? implode(', ', [
            rtrim($appUrl, '/').'/webhooks/komerce/payment',
            rtrim($appUrl, '/').'/webhooks/komerce/delivery',
        ]) : 'APP_URL bukan HTTPS publik ('.(parse_url($appUrl, PHP_URL_HOST) ?: $appUrl).')';

        if ($keysOk && $public && ! $paymentSandbox && ! $deliverySandbox && $routesOk) {
            $this->gate(
                'production_keys_and_webhook',
                'Key production + webhook URL publik terdaftar di Collaborator',
                'PARTIAL',
                'Kunci terisi, host payment/delivery bukan sandbox, route webhook ada. Pendaftaran di dashboard Collaborator tidak bisa diverifikasi dari app. Daftarkan: '.$webhookUrls,
            );

            return;
        }

        $this->gate(
            'production_keys_and_webhook',
            'Key production + webhook URL publik terdaftar di Collaborator',
            'FAIL',
            'keys payment='.$this->yn($keys['payment'])
            .' cost='.$this->yn($keys['cost'])
            .' delivery='.$this->yn($keys['delivery'])
            .' webhook_secret='.$this->yn($keys['webhook_secret'])
            .'; payment_host='.($paymentSandbox ? 'sandbox' : 'not-sandbox')
            .'; delivery_host='.($deliverySandbox ? 'sandbox' : 'not-sandbox')
            .'; public_https='.$this->yn($public)
            .'; routes='.$this->yn($routesOk)
            .'; '.$webhookUrls
            .'. Collaborator dashboard tidak dicek dari sini.',
        );
    }

    private function scoreProductionDatabase(): void
    {
        $connection = (string) config('database.default');
        $driver = (string) config('database.connections.'.$connection.'.driver', $connection);
        $backupPackages = $this->installedBackupPackages();
        $backupDir = storage_path('app/backups');
        $hasBackupFiles = is_dir($backupDir) && count(glob($backupDir.'/*') ?: []) > 0;
        $backupEnv = filled(env('BACKUP_DISK')) || filled(env('DB_BACKUP_PATH'));

        if (in_array($driver, ['sqlite', ''], true)) {
            $this->gate(
                'production_database_and_backup',
                'Database production (bukan SQLite) + backup',
                'FAIL',
                'DB_CONNECTION='.$connection.' driver='.$driver.'. MySQL lokal bisa saja hidup, tapi app ini masih SQLite. Paket backup='.($backupPackages === [] ? 'tidak ada' : implode(',', $backupPackages)).' file backup='.$this->yn($hasBackupFiles).' env backup='.$this->yn($backupEnv).'.',
            );

            return;
        }

        if ($hasBackupFiles || $backupEnv || $backupPackages !== []) {
            $this->gate(
                'production_database_and_backup',
                'Database production (bukan SQLite) + backup',
                'PASS',
                'driver='.$driver.'; backup terdeteksi.',
            );

            return;
        }

        $this->gate(
            'production_database_and_backup',
            'Database production (bukan SQLite) + backup',
            'PARTIAL',
            'driver='.$driver.' (bukan SQLite) tetapi tidak ada paket/file/env backup yang terdeteksi.',
        );
    }

    private function scoreQueueAndScheduler(): void
    {
        $queue = (string) config('queue.default');
        $pending = Schema::hasTable('jobs') ? (int) DB::table('jobs')->count() : 0;
        $failed = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
        $events = collect(app(Schedule::class)->events())
            ->map(static fn ($event): string => (string) ($event->command ?? $event->description ?? ''))
            ->filter()
            ->values();
        $scheduled = [
            'komerce:fulfill-paid-orders' => $events->contains(static fn (string $cmd): bool => str_contains($cmd, 'komerce:fulfill-paid-orders')),
            'komerce:refresh-shipment-tracking' => $events->contains(static fn (string $cmd): bool => str_contains($cmd, 'komerce:refresh-shipment-tracking')),
            'komerce:expire-unpaid-orders' => $events->contains(static fn (string $cmd): bool => str_contains($cmd, 'komerce:expire-unpaid-orders')),
        ];
        $scheduleOk = ! in_array(false, $scheduled, true);

        $commands = $this->runningArtisanCommands();
        $worker = $this->hasProcess($commands, 'queue:work') || $this->hasProcess($commands, 'queue:listen') || $this->hasProcess($commands, 'horizon');
        $scheduler = $this->hasProcess($commands, 'schedule:work') || $this->hasProcess($commands, 'schedule:run');

        if ($queue !== 'sync' && $worker && $scheduler && $scheduleOk) {
            $this->gate(
                'queue_worker_and_scheduler',
                'Queue worker + scheduler jalan 24/7 di server',
                'PASS',
                'queue='.$queue.' worker=yes scheduler=yes pending='.$pending.' failed='.$failed.'. Ini deteksi proses saat ini, bukan jaminan systemd/supervisor.',
            );

            return;
        }

        $this->gate(
            'queue_worker_and_scheduler',
            'Queue worker + scheduler jalan 24/7 di server',
            'FAIL',
            'queue='.$queue
            .' worker='.$this->yn($worker)
            .' scheduler_process='.$this->yn($scheduler)
            .' schedule_defined='.$this->yn($scheduleOk)
            .' pending_jobs='.$pending
            .' failed_jobs='.$failed
            .'. Wajib queue:work/listen + cron `schedule:run` atau `schedule:work` 24/7 (supervisor/systemd). Proses terdeteksi: '.($commands === [] ? 'tidak ada artisan queue/schedule' : implode(' | ', $commands)).'.',
        );
    }

    /**
     * @param  list<array{label: string, http: int, ok: bool, host: string, error?: string}>  $probes
     */
    private function scoreInventoryOrigins(array $probes): void
    {
        if (! Schema::hasTable('sh_inventories')) {
            $this->gate(
                'inventory_rajaongkir_origins',
                'Origin gudang + destinasi RajaOngkir lengkap di semua lokasi',
                'FAIL',
                'Tabel sh_inventories tidak ada.',
            );

            return;
        }

        $inventories = Inventory::query()->get(['id', 'name', 'city', 'postal_code', 'rajaongkir_origin_id']);
        if ($inventories->isEmpty()) {
            $this->gate(
                'inventory_rajaongkir_origins',
                'Origin gudang + destinasi RajaOngkir lengkap di semua lokasi',
                'FAIL',
                'Tidak ada lokasi/gudang.',
            );

            return;
        }

        $missing = $inventories
            ->filter(static fn (Inventory $inventory): bool => trim((string) $inventory->getAttribute('rajaongkir_origin_id')) === '')
            ->map(static fn (Inventory $inventory): string => '#'.$inventory->id.' '.$inventory->name)
            ->values()
            ->all();

        $rows = $inventories
            ->map(static function (Inventory $inventory): string {
                $origin = trim((string) $inventory->getAttribute('rajaongkir_origin_id'));

                return $inventory->name.' city='.(string) $inventory->city.' origin='.($origin !== '' ? $origin : 'KOSONG');
            })
            ->implode('; ');

        $costLive = $this->probeOk($probes, 'cost_destination');

        if ($missing !== []) {
            $this->gate(
                'inventory_rajaongkir_origins',
                'Origin gudang + destinasi RajaOngkir lengkap di semua lokasi',
                'FAIL',
                'Origin kosong: '.implode(', ', $missing).'. '.$rows,
            );

            return;
        }

        if ($costLive && komerce_shipping_cost_enabled()) {
            $this->gate(
                'inventory_rajaongkir_origins',
                'Origin gudang + destinasi RajaOngkir lengkap di semua lokasi',
                'PASS',
                'Semua '.$inventories->count().' lokasi punya rajaongkir_origin_id; Cost destination search 2xx. '.$rows,
            );

            return;
        }

        $this->gate(
            'inventory_rajaongkir_origins',
            'Origin gudang + destinasi RajaOngkir lengkap di semua lokasi',
            'PARTIAL',
            'Semua '.$inventories->count().' lokasi punya origin id, tapi Cost key/search live belum memverifikasi id itu di RajaOngkir. '.$rows.'. Probe: '.$this->probeSummary($probes, 'cost_destination'),
        );
    }

    private function scoreVolumeMonitoring(): void
    {
        $failedJobs = Schema::hasTable('failed_jobs') ? (int) DB::table('failed_jobs')->count() : 0;
        $awbErrors = 0;
        if (Schema::hasTable('order_shipments')) {
            $awbErrors = OrderShipment::query()
                ->get(['metadata'])
                ->filter(static fn (OrderShipment $shipment): bool => filled(data_get($shipment->metadata, 'komerce.fulfillment_error')))
                ->count();
        }

        $sentry = filled(env('SENTRY_LARAVEL_DSN')) || filled(config('sentry.dsn'));
        $horizon = class_exists('Laravel\\Horizon\\Horizon');
        $nightwatch = filter_var(env('NIGHTWATCH_ENABLED'), FILTER_VALIDATE_BOOLEAN);
        $pulse = class_exists('Laravel\\Pulse\\Pulse') && (bool) config('pulse.enabled', false);

        if ($sentry && ($horizon || $pulse || $nightwatch)) {
            $this->gate(
                'volume_monitoring_oncall',
                'Volume/load, monitoring gagal AWB, on-call',
                'PARTIAL',
                'Ada alat observability, tetapi load test + rotasi on-call masih harus dikonfirmasi manusia. failed_jobs='.$failedJobs.' fulfillment_error='.$awbErrors.'.',
            );

            return;
        }

        $this->gate(
            'volume_monitoring_oncall',
            'Volume/load, monitoring gagal AWB, on-call',
            'FAIL',
            'Tidak ada Sentry/Horizon/Pulse/Nightwatch. Gagal AWB hanya tersimpan di metadata shipment (fulfillment_error='.$awbErrors.'), failed_jobs='.$failedJobs.'. Belum ada load test, alert, atau on-call.',
        );
    }

    /**
     * @return list<array{label: string, http: int, ok: bool, host: string, error?: string}>
     */
    private function liveProbes(): array
    {
        $paymentBase = rtrim((string) config('komerce.payment_base_url'), '/');
        $costBase = rtrim((string) config('komerce.rajaongkir.cost_base_url'), '/');
        $deliveryBase = rtrim((string) config('komerce.rajaongkir.delivery_base_url'), '/');

        return [
            $this->probe(
                'payment_methods',
                $paymentBase.'/api/v1/user/methods',
                array_filter(['x-api-key' => trim((string) config('komerce.payment_api_key', ''))]),
            ),
            $this->probe(
                'cost_destination',
                $costBase.'/api/v1/destination/domestic-destination?search=Cirebon&limit=1&offset=0',
                array_filter(['key' => trim((string) config('komerce.shipping_cost_api_key', ''))]),
            ),
            $this->probe(
                'delivery_destination',
                $deliveryBase.'/tariff/api/v1/destination/search?keyword=Cirebon',
                array_filter(['x-api-key' => trim((string) config('komerce.shipping_delivery_api_key', ''))]),
            ),
        ];
    }

    /**
     * @param  array<string, string>  $headers
     * @return array{label: string, http: int, ok: bool, host: string, error?: string}
     */
    private function probe(string $label, string $url, array $headers): array
    {
        $host = (string) (parse_url($url, PHP_URL_HOST) ?: '');

        if ($url === '' || $host === '' || ! str_starts_with($url, 'http')) {
            return ['label' => $label, 'http' => 0, 'ok' => false, 'host' => $host, 'error' => 'base URL kosong'];
        }

        try {
            $response = Http::connectTimeout(3)
                ->timeout(8)
                ->acceptJson()
                ->withHeaders($headers)
                ->get($url);

            return [
                'label' => $label,
                'http' => $response->status(),
                'ok' => $response->successful(),
                'host' => $host,
            ];
        } catch (Throwable $e) {
            return [
                'label' => $label,
                'http' => 0,
                'ok' => false,
                'host' => $host,
                'error' => $this->redact(class_basename($e).': '.$e->getMessage()),
            ];
        }
    }

    /**
     * @param  list<array{label: string, http: int, ok: bool, host: string, error?: string}>  $probes
     */
    private function probeOk(array $probes, string $label): bool
    {
        foreach ($probes as $probe) {
            if ($probe['label'] === $label) {
                return $probe['ok'];
            }
        }

        return false;
    }

    /**
     * @param  list<array{label: string, http: int, ok: bool, host: string, error?: string}>  $probes
     */
    private function probeSummary(array $probes, ?string $only = null): string
    {
        $bits = [];
        foreach ($probes as $probe) {
            if ($only !== null && $probe['label'] !== $only) {
                continue;
            }
            $bits[] = $probe['label'].' http='.$probe['http']
                .($probe['ok'] ? ' ok' : ' fail')
                .(isset($probe['error']) ? ' '.$probe['error'] : '');
        }

        return $bits === [] ? 'skipped' : implode('; ', $bits);
    }

    /**
     * @return list<string>
     */
    private function runningArtisanCommands(): array
    {
        if (PHP_OS_FAMILY === 'Windows') {
            return [];
        }

        try {
            $result = Process::timeout(3)->run(['ps', '-ax', '-o', 'command=']);
        } catch (Throwable) {
            return [];
        }

        if (! $result->successful()) {
            return [];
        }

        $matched = [];
        foreach (preg_split('/\n/', $result->output()) ?: [] as $line) {
            $line = trim($line);
            if ($line === '' || ! str_contains($line, 'artisan')) {
                continue;
            }
            if (
                str_contains($line, 'queue:work')
                || str_contains($line, 'queue:listen')
                || str_contains($line, 'schedule:work')
                || str_contains($line, 'schedule:run')
                || str_contains($line, 'horizon')
            ) {
                $matched[] = $this->redact($line);
            }
        }

        return array_values(array_unique($matched));
    }

    /**
     * @param  list<string>  $commands
     */
    private function hasProcess(array $commands, string $needle): bool
    {
        foreach ($commands as $command) {
            if (str_contains($command, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function isPublicHttpsUrl(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        if ($scheme !== 'https' || $host === '') {
            return false;
        }

        if ($host === 'localhost' || $host === '::1' || str_starts_with($host, '127.')) {
            return false;
        }

        foreach (['.local', '.test', '.localhost', '.internal'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        return true;
    }

    private function isSandboxUrl(string $url): bool
    {
        return str_contains(strtolower($url), 'sandbox');
    }

    /**
     * @return list<string>
     */
    private function installedBackupPackages(): array
    {
        $lockPath = base_path('composer.lock');
        if (! is_file($lockPath)) {
            return [];
        }

        $lock = json_decode((string) file_get_contents($lockPath), true);
        if (! is_array($lock)) {
            return [];
        }

        $names = [];
        foreach (array_merge($lock['packages'] ?? [], $lock['packages-dev'] ?? []) as $package) {
            $name = (string) ($package['name'] ?? '');
            if ($name !== '' && str_contains($name, 'backup')) {
                $names[] = $name;
            }
        }

        return $names;
    }

    private function yn(bool $value): string
    {
        return $value ? 'yes' : 'no';
    }

    private function redact(string $text): string
    {
        foreach ($this->secrets as $secret) {
            $text = str_replace($secret, '[redacted]', $text);
        }

        return $text;
    }

    private function gate(string $id, string $syarat, string $status, string $detail): void
    {
        $this->gates[] = [
            'id' => $id,
            'syarat' => $syarat,
            'status' => $status,
            'detail' => $this->redact($detail),
        ];
    }
}
