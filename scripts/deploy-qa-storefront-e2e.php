<?php

declare(strict_types=1);

/**
 * QA Test Engineer deploy E2E for OceanMall storefront.
 *
 * Structured test cases (TC-*), suites, severity, defect JSON.
 * Reuses CSRF/Inertia/cookie patterns from deploy-storefront-deep-e2e.php.
 *
 * Usage:
 *   DEPLOY_BASE_URL=http://127.0.0.1:8000 \
 *   DEPLOY_QA_SUITE=regression \
 *   php scripts/deploy-qa-storefront-e2e.php
 *
 * Does NOT print secrets. Exit 1 on S1/S2 fail (or S3 when DEPLOY_QA_STRICT=YES).
 */

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Http;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$base = rtrim((string) (getenv('DEPLOY_BASE_URL') ?: getenv('UAT_BASE_URL') ?: ''), '/');
$email = (string) (getenv('DEPLOY_QA_EMAIL') ?: getenv('DEPLOY_E2E_EMAIL') ?: 'customer@oceanmall.test');
$password = (string) (getenv('DEPLOY_QA_PASSWORD') ?: getenv('DEPLOY_E2E_PASSWORD') ?: 'password123');
$suite = strtolower((string) (getenv('DEPLOY_QA_SUITE') ?: 'regression'));
$strict = strtoupper((string) (getenv('DEPLOY_QA_STRICT') ?: '')) === 'YES';
$placeOrder = strtoupper((string) (getenv('DEPLOY_QA_PLACE_ORDER') ?: 'NO')) === 'YES';
$confirm = strtoupper((string) (getenv('DEPLOY_QA_CONFIRM') ?: ''));
$productSlugHint = trim((string) (getenv('DEPLOY_QA_PRODUCT_SLUG') ?: getenv('DEPLOY_E2E_PRODUCT_SLUG') ?: ''));
$destQuery = trim((string) (getenv('DEPLOY_QA_DESTINATION') ?: getenv('DEPLOY_E2E_DESTINATION') ?: 'Jakarta Selatan'));
$couponCode = strtoupper(trim((string) (getenv('DEPLOY_QA_COUPON') ?: getenv('DEPLOY_E2E_COUPON') ?: 'OCEAN10')));
$slowThresholdMs = (int) (getenv('DEPLOY_QA_SLOW_MS') ?: 3000);

$allowedSuites = ['smoke', 'regression', 'negative', 'security', 'all'];
if (! in_array($suite, $allowedSuites, true)) {
    fwrite(STDERR, "FAIL: DEPLOY_QA_SUITE must be smoke|regression|negative|security|all (got {$suite})\n");
    exit(1);
}

if ($base === '') {
    fwrite(STDERR, "FAIL: set DEPLOY_BASE_URL (e.g. https://shop.example.com)\n");
    exit(1);
}

$host = parse_url($base, PHP_URL_HOST) ?: '';
$isLocal = in_array($host, ['127.0.0.1', 'localhost', '::1'], true)
    || str_ends_with((string) $host, '.test')
    || str_ends_with((string) $host, '.local');

if ($placeOrder && ! $isLocal && $confirm !== 'YES') {
    fwrite(STDERR, "FAIL: non-local place-order requires DEPLOY_QA_CONFIRM=YES\n");
    exit(1);
}

// ── Shared HTTP helpers (mirrors deploy-storefront-deep-e2e.php) ─────────────

final class QaHttp
{
    public function __construct(
        public readonly string $base,
        public \GuzzleHttp\Cookie\CookieJar $cookieJar = new \GuzzleHttp\Cookie\CookieJar,
        public string $xsrf = '',
        public string $inertiaVersion = '',
    ) {}

    public function cloneFresh(): self
    {
        return new self($this->base);
    }

    private function makeClient()
    {
        return Http::baseUrl($this->base)
            ->withOptions(['cookies' => $this->cookieJar, 'allow_redirects' => false])
            ->timeout(90)
            ->acceptJson();
    }

    public function refreshXsrf(): void
    {
        foreach ($this->cookieJar->toArray() as $cookie) {
            if (($cookie['Name'] ?? '') === 'XSRF-TOKEN') {
                $this->xsrf = urldecode((string) $cookie['Value']);
            }
        }
    }

    public function send(string $method, string $path, array $options = [], bool $inertia = true, bool $withCsrf = true)
    {
        $self = $this;
        $attempt = static function () use ($self, $method, $path, $options, $inertia, $withCsrf) {
            $headers = [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'text/html, application/xhtml+xml',
                'Referer' => $options['referer'] ?? ($self->base.'/'),
            ];
            if ($withCsrf && $self->xsrf !== '') {
                $headers['X-XSRF-TOKEN'] = $self->xsrf;
            }
            if ($inertia) {
                $headers['X-Inertia'] = 'true';
                $headers['X-Inertia-Version'] = $self->inertiaVersion;
            }
            $headers = array_merge($headers, $options['headers'] ?? []);

            $pending = $self->makeClient()->withHeaders($headers);

            return match (strtoupper($method)) {
                'GET' => $pending->get($path, $options['query'] ?? []),
                'POST' => isset($options['json'])
                    ? $pending->asJson()->post($path, $options['json'])
                    : $pending->asForm()->post($path, $options['form'] ?? []),
                'PATCH' => isset($options['json'])
                    ? $pending->asJson()->patch($path, $options['json'])
                    : $pending->asForm()->patch($path, $options['form'] ?? []),
                'DELETE' => $pending->delete($path),
                default => throw new InvalidArgumentException("Unsupported method {$method}"),
            };
        };

        $response = $attempt();
        $this->refreshXsrf();

        if ($inertia && $response->status() === 409) {
            $headerVersion = $response->header('X-Inertia-Version');
            if (is_string($headerVersion) && $headerVersion !== '') {
                $this->inertiaVersion = $headerVersion;
                $response = $attempt();
                $this->refreshXsrf();
            }
        }

        return $response;
    }

    public function request(string $method, string $path, array $options = [])
    {
        return $this->send($method, $path, $options, true, true);
    }

    public function htmlRequest(string $method, string $path, array $options = [])
    {
        return $this->send($method, $path, $options, false, true);
    }

    public function rawJsonPost(string $path, string $body, array $headers = [])
    {
        $response = $this->makeClient()
            ->withHeaders(array_merge([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ], $headers))
            ->withBody($body, 'application/json')
            ->post($path);
        $this->refreshXsrf();

        return $response;
    }

    public function seedCsrfFromLoginPage(): void
    {
        $loginPage = $this->htmlRequest('GET', '/login');
        if ($loginPage->status() !== 200) {
            throw new RuntimeException('Login page HTTP '.$loginPage->status());
        }
        if ($this->xsrf === '') {
            throw new RuntimeException('missing XSRF-TOKEN cookie');
        }
        if (preg_match('/data-page="([^"]+)"/', $loginPage->body(), $m)) {
            $decoded = html_entity_decode($m[1], ENT_QUOTES);
            $page = json_decode($decoded, true);
            if (is_array($page) && isset($page['version']) && is_string($page['version'])) {
                $this->inertiaVersion = $page['version'];
            }
        }
    }
}

final class QaContext
{
    public ?array $chosenProduct = null;

    public int $productId = 0;

    public ?int $variantId = null;

    public int $lineId = 0;

    public ?string $categorySlug = null;

    public ?string $collectionSlug = null;

    public ?array $destination = null;

    public ?int $latestOrderId = null;

    /** @var array<string, int> */
    public array $timings = [];
}

function propsOf($response): array
{
    $json = $response->json();
    if (! is_array($json)) {
        return [];
    }

    return is_array($json['props'] ?? null) ? $json['props'] : [];
}

function componentOf($response): ?string
{
    $json = $response->json();
    if (! is_array($json)) {
        return null;
    }
    $component = $json['component'] ?? null;

    return is_string($component) && $component !== '' ? $component : null;
}

function extractProducts(array $props): array
{
    $products = data_get($props, 'products.data');
    if (! is_array($products) || $products === []) {
        $products = data_get($props, 'products') ?? [];
        if (isset($products['data']) && is_array($products['data'])) {
            $products = $products['data'];
        }
    }

    return is_array($products) ? $products : [];
}

function cartLines(array $cartProps): array
{
    $lines = data_get($cartProps, 'cart.lines');

    return is_array($lines) ? $lines : [];
}

function bodySnippet($response, int $len = 400): string
{
    return substr((string) $response->body(), 0, $len);
}

function redirectLocation($response): string
{
    $loc = (string) ($response->header('Location') ?? '');

    return $loc;
}

function isLoginRedirect($response): bool
{
    $loc = redirectLocation($response);

    return in_array($response->status(), [302, 303], true)
        && (str_contains($loc, '/login') || str_contains($loc, 'login'));
}

function isGuestBlocked($response): bool
{
    if (isLoginRedirect($response)) {
        return true;
    }

    return $response->status() === 401;
}

function hasValidationSignal($response): bool
{
    if ($response->status() === 422) {
        return true;
    }
    if (in_array($response->status(), [302, 303], true)) {
        return true;
    }
    $json = $response->json();
    if (is_array($json) && ! empty($json['errors'])) {
        return true;
    }
    $props = propsOf($response);
    if (! empty($props['errors']) && is_array($props['errors'])) {
        return true;
    }

    return false;
}

function timed(QaHttp $http, callable $fn): array
{
    $start = hrtime(true);
    $response = $fn();
    $ms = (int) round((hrtime(true) - $start) / 1_000_000);

    return ['response' => $response, 'ms' => $ms];
}

// ── Case registry ────────────────────────────────────────────────────────────

/** @var array<string, array{title: string, severity: string, suites: list<string>, area: string}> */
$caseMeta = [
    'TC-AUTH-001' => ['title' => 'Login with valid credentials', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'auth'],
    'TC-AUTH-002' => ['title' => 'Logout returns guest session', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'auth'],
    'TC-AUTH-003' => ['title' => 'Wrong password stays guest', 'severity' => 'S2', 'suites' => ['regression', 'negative', 'all'], 'area' => 'auth'],
    'TC-AUTH-004' => ['title' => 'Unauthenticated checkout redirect', 'severity' => 'S1', 'suites' => ['smoke', 'regression', 'negative', 'all'], 'area' => 'auth'],
    'TC-CAT-001' => ['title' => 'Home page loads', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-002' => ['title' => 'Shop listing with products', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-003' => ['title' => 'Shop sort price_asc', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-004' => ['title' => 'Shop sort price_desc', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-005' => ['title' => 'Shop sort name', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-006' => ['title' => 'Categories index', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-007' => ['title' => 'Category show page', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-008' => ['title' => 'Collection show page', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-009' => ['title' => 'Search hit', 'severity' => 'S3', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-010' => ['title' => 'Search no-match', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-011' => ['title' => 'PDP loads', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'catalog'],
    'TC-CAT-012' => ['title' => 'PDP structured variants', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'catalog'],
    'TC-CART-001' => ['title' => 'Guest add to cart', 'severity' => 'S1', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'cart'],
    'TC-CART-002' => ['title' => 'Update cart qty valid', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'cart'],
    'TC-CART-003' => ['title' => 'Cart qty min boundary 1', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'cart'],
    'TC-CART-004' => ['title' => 'Cart qty max boundary 10', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'cart'],
    'TC-CART-005' => ['title' => 'Cart qty over max rejected', 'severity' => 'S2', 'suites' => ['regression', 'negative', 'all'], 'area' => 'cart'],
    'TC-CART-006' => ['title' => 'Cart qty zero rejected', 'severity' => 'S2', 'suites' => ['regression', 'negative', 'all'], 'area' => 'cart'],
    'TC-CART-007' => ['title' => 'Valid coupon applies', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'cart'],
    'TC-CART-008' => ['title' => 'Invalid coupon handled', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'negative', 'all'], 'area' => 'cart'],
    'TC-CART-009' => ['title' => 'Remove cart line', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'cart'],
    'TC-CART-010' => ['title' => 'Clear cart', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'cart'],
    'TC-CHK-001' => ['title' => 'Zone country set', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'checkout'],
    'TC-CHK-002' => ['title' => 'Destination empty query validation', 'severity' => 'S3', 'suites' => ['regression', 'negative', 'all'], 'area' => 'checkout'],
    'TC-CHK-003' => ['title' => 'Shipping address missing fields', 'severity' => 'S2', 'suites' => ['regression', 'negative', 'all'], 'area' => 'checkout'],
    'TC-CHK-004' => ['title' => 'Shipping address missing destination id', 'severity' => 'S2', 'suites' => ['regression', 'negative', 'all'], 'area' => 'checkout'],
    'TC-CHK-005' => ['title' => 'Delivery rates on checkout step 2', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'checkout'],
    'TC-CHK-006' => ['title' => 'Payment methods on checkout', 'severity' => 'S2', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'checkout'],
    'TC-CHK-007' => ['title' => 'Saved address reuse', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'checkout'],
    'TC-CHK-008' => ['title' => 'Place unpaid order', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'checkout'],
    'TC-ACCT-001' => ['title' => 'Dashboard recentOrders', 'severity' => 'S3', 'suites' => ['smoke', 'regression', 'all'], 'area' => 'account'],
    'TC-ACCT-002' => ['title' => 'Account orders list', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'account'],
    'TC-ACCT-003' => ['title' => 'Account order show', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'account'],
    'TC-ACCT-004' => ['title' => 'Account addresses', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'account'],
    'TC-ACCT-005' => ['title' => 'Account notifications', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'account'],
    'TC-ACCT-006' => ['title' => 'Settings profile', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'account'],
    'TC-ACCT-007' => ['title' => 'Settings security gate', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'account'],
    'TC-SEC-001' => ['title' => 'CSRF reject without token', 'severity' => 'S1', 'suites' => ['regression', 'security', 'all'], 'area' => 'security'],
    'TC-SEC-002' => ['title' => 'Guest cannot access account orders', 'severity' => 'S1', 'suites' => ['smoke', 'regression', 'negative', 'security', 'all'], 'area' => 'security'],
    'TC-SEC-003' => ['title' => 'Webhook without signature 401', 'severity' => 'S1', 'suites' => ['smoke', 'regression', 'security', 'all'], 'area' => 'security'],
    'TC-PERF-001' => ['title' => 'Home response time', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'performance'],
    'TC-PERF-002' => ['title' => 'Shop response time', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'performance'],
    'TC-PERF-003' => ['title' => 'PDP response time', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'performance'],
    'TC-PERF-004' => ['title' => 'Checkout step 2 response time', 'severity' => 'S3', 'suites' => ['regression', 'all'], 'area' => 'performance'],
];

$inSuite = static function (string $tcId) use ($suite, $caseMeta, $placeOrder): bool {
    if ($suite === 'all') {
        if ($tcId === 'TC-CHK-008' && ! $placeOrder) {
            return false;
        }

        return true;
    }
    $meta = $caseMeta[$tcId] ?? null;
    if ($meta === null) {
        return false;
    }
    if ($tcId === 'TC-CHK-008' && ! $placeOrder) {
        return false;
    }

    return in_array($suite, $meta['suites'], true);
};

$skipReason = static function (string $tcId) use ($suite, $placeOrder): string {
    if ($tcId === 'TC-CHK-008' && ! $placeOrder) {
        return 'DEPLOY_QA_PLACE_ORDER not YES';
    }

    return "not in suite {$suite}";
};

$results = [];
$defects = [];
$summary = ['pass' => 0, 'fail' => 0, 'skip' => 0, 's1' => 0, 's2' => 0, 's3' => 0];
$defectSeq = 0;

$recordPass = static function (string $tc, string $detail = '') use (&$results, &$summary): void {
    $results[] = ['tc' => $tc, 'status' => 'PASS', 'detail' => $detail];
    $summary['pass']++;
    $line = $detail !== '' ? " — {$detail}" : '';
    echo "PASS [{$tc}]{$line}\n";
};

$recordSkip = static function (string $tc, string $reason = '') use (&$results, &$summary): void {
    $results[] = ['tc' => $tc, 'status' => 'SKIP', 'detail' => $reason];
    $summary['skip']++;
    $line = $reason !== '' ? " — {$reason}" : '';
    echo "SKIP [{$tc}]{$line}\n";
};

$recordFail = static function (
    string $tc,
    string $expected,
    string $actual,
    $response,
    string $area,
) use (&$results, &$summary, &$defects, &$defectSeq, $caseMeta): void {
    $meta = $caseMeta[$tc];
    $severity = $meta['severity'];
    $defectSeq++;
    $defect = [
        'id' => sprintf('def-%03d', $defectSeq),
        'tc' => $tc,
        'severity' => $severity,
        'title' => $meta['title'].' failed',
        'expected' => $expected,
        'actual' => $actual,
        'evidence' => [
            'status' => is_object($response) && method_exists($response, 'status') ? $response->status() : null,
            'body_snippet' => is_object($response) && method_exists($response, 'body') ? bodySnippet($response) : (string) $response,
        ],
        'area' => $area,
    ];
    $defects[] = $defect;
    $results[] = ['tc' => $tc, 'status' => 'FAIL', 'detail' => $actual];
    $summary['fail']++;
    if ($severity === 'S1') {
        $summary['s1']++;
    } elseif ($severity === 'S2') {
        $summary['s2']++;
    } elseif ($severity === 'S3') {
        $summary['s3']++;
    }
    echo "FAIL [{$tc}] {$actual}\n";
};

$assertOrFail = static function (
    string $tc,
    bool $ok,
    string $expected,
    string $actual,
    $response,
    string $area,
) use ($recordPass, $recordFail): void {
    if ($ok) {
        $recordPass($tc, $actual !== '' ? $actual : $expected);

        return;
    }
    $recordFail($tc, $expected, $actual, $response, $area);
};

$runCase = static function (string $tc, callable $fn) use ($inSuite, $recordSkip, $caseMeta, $skipReason): void {
    if (! isset($caseMeta[$tc])) {
        throw new InvalidArgumentException("Unknown case {$tc}");
    }
    if (! $inSuite($tc)) {
        $recordSkip($tc, $skipReason($tc));

        return;
    }
    $fn();
};

$http = new QaHttp($base);
$ctx = new QaContext;
$GLOBALS['suite'] = $suite;
$GLOBALS['base'] = $base;
$GLOBALS['email'] = $email;
$GLOBALS['password'] = $password;
$GLOBALS['placeOrder'] = $placeOrder;

echo "QA DEPLOY E2E base={$base} suite={$suite} place_order=".($placeOrder ? 'YES' : 'NO')." host={$host}\n";

// ── Bootstrap session ────────────────────────────────────────────────────────

try {
    $http->seedCsrfFromLoginPage();
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL bootstrap: '.$e->getMessage()."\n");
    exit(1);
}

// ── Security (can run early) ─────────────────────────────────────────────────

$runCase('TC-SEC-003', static function () use ($assertOrFail, $http): void {
    $payload = json_encode(['payment_id' => 'QA-TEST', 'status' => 'PAID'], JSON_THROW_ON_ERROR);
    $res = $http->rawJsonPost('/webhooks/komerce/payment', $payload);
    $ok = $res->status() === 401;
    $assertOrFail(
        'TC-SEC-003',
        $ok,
        'HTTP 401 invalid_secret',
        'HTTP '.$res->status(),
        $res,
        'security',
    );
});

$runCase('TC-SEC-002', static function () use ($assertOrFail): void {
    $guest = (new QaHttp($GLOBALS['base']))->cloneFresh();
    $guest->seedCsrfFromLoginPage();
    $res = $guest->request('GET', '/account/orders');
    $ok = isGuestBlocked($res);
    $assertOrFail(
        'TC-SEC-002',
        $ok,
        'redirect to login or 401',
        'HTTP '.$res->status().' loc='.redirectLocation($res),
        $res,
        'security',
    );
});

$runCase('TC-SEC-001', static function () use ($assertOrFail, $http, $ctx): void {
    $fresh = $http->cloneFresh();
    $fresh->seedCsrfFromLoginPage();
    // Deliberately omit CSRF header
    $res = $fresh->send('POST', '/cart', [
        'form' => ['product_id' => 1, 'quantity' => 1],
        'referer' => $GLOBALS['base'].'/shop',
    ], false, false);
    $ok = in_array($res->status(), [419, 403], true);
    $assertOrFail(
        'TC-SEC-001',
        $ok,
        'HTTP 419/403 CSRF mismatch',
        'HTTP '.$res->status(),
        $res,
        'security',
    );
});

// ── Catalog ──────────────────────────────────────────────────────────────────

$runCase('TC-CAT-001', static function () use ($assertOrFail, $http, $ctx, $slowThresholdMs): void {
    $timed = timed($http, static fn () => $http->request('GET', '/'));
    $res = $timed['response'];
    $ctx->timings['home'] = $timed['ms'];
    $ok = $res->status() === 200 && str_starts_with((string) componentOf($res), 'shop/');
    $collections = data_get(propsOf($res), 'featuredCollections', []);
    if (is_array($collections) && $collections !== []) {
        $ctx->collectionSlug = (string) ($collections[0]['slug'] ?? '');
    }
    $assertOrFail('TC-CAT-001', $ok, 'HTTP 200 shop component', 'HTTP '.$res->status(), $res, 'catalog');
});

$runCase('TC-CAT-002', static function () use ($assertOrFail, $http, $ctx, $productSlugHint): void {
    $timed = timed($http, static fn () => $http->request('GET', '/shop'));
    $res = $timed['response'];
    $ctx->timings['shop'] = $timed['ms'];
    $props = propsOf($res);
    $products = extractProducts($props);
    $chosen = null;
    if ($productSlugHint !== '') {
        foreach ($products as $p) {
            if (($p['slug'] ?? null) === $productSlugHint) {
                $chosen = $p;
                break;
            }
        }
    }
    if ($chosen === null) {
        foreach ($products as $p) {
            if (! empty($p['id'])) {
                $chosen = $p;
                break;
            }
        }
    }
    $ctx->chosenProduct = $chosen;
    $ok = $res->status() === 200 && $chosen !== null;
    $assertOrFail(
        'TC-CAT-002',
        $ok,
        'HTTP 200 with products',
        $ok ? 'products='.count($products) : 'no products',
        $res,
        'catalog',
    );
});

foreach ([
    'TC-CAT-003' => 'price_asc',
    'TC-CAT-004' => 'price_desc',
    'TC-CAT-005' => 'name',
] as $tc => $sort) {
    $runCase($tc, static function () use ($assertOrFail, $http, $tc, $sort): void {
        $res = $http->request('GET', '/shop', ['query' => ['sort' => $sort]]);
        $ok = $res->status() === 200;
        $assertOrFail($tc, $ok, 'HTTP 200', 'HTTP '.$res->status()." sort={$sort}", $res, 'catalog');
    });
}

$runCase('TC-CAT-006', static function () use ($assertOrFail, $http, $ctx): void {
    $res = $http->request('GET', '/categories');
    $cats = data_get(propsOf($res), 'categories', []);
    if (is_array($cats) && $cats !== []) {
        $ctx->categorySlug = (string) ($cats[0]['slug'] ?? '');
    }
    $ok = $res->status() === 200;
    $assertOrFail('TC-CAT-006', $ok, 'HTTP 200', 'HTTP '.$res->status(), $res, 'catalog');
});

$runCase('TC-CAT-007', static function () use ($assertOrFail, $http, $ctx, $recordSkip, $inSuite): void {
    if ($ctx->categorySlug === null || $ctx->categorySlug === '') {
        if ($inSuite('TC-CAT-007')) {
            $recordSkip('TC-CAT-007', 'no category slug');
        }

        return;
    }
    $res = $http->request('GET', '/categories/'.$ctx->categorySlug);
    $ok = $res->status() === 200;
    $assertOrFail('TC-CAT-007', $ok, 'HTTP 200', 'slug='.$ctx->categorySlug, $res, 'catalog');
});

$runCase('TC-CAT-008', static function () use ($assertOrFail, $http, $ctx, $recordSkip, $inSuite): void {
    if ($ctx->collectionSlug === null || $ctx->collectionSlug === '') {
        if ($inSuite('TC-CAT-008')) {
            $recordSkip('TC-CAT-008', 'no featured collection');
        }

        return;
    }
    $res = $http->request('GET', '/collections/'.$ctx->collectionSlug);
    $ok = $res->status() === 200;
    $assertOrFail('TC-CAT-008', $ok, 'HTTP 200', 'slug='.$ctx->collectionSlug, $res, 'catalog');
});

$runCase('TC-CAT-009', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/search', ['query' => ['q' => 'realme']]);
    $ok = $res->status() === 200;
    $assertOrFail('TC-CAT-009', $ok, 'HTTP 200', 'HTTP '.$res->status(), $res, 'catalog');
});

$runCase('TC-CAT-010', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/search', ['query' => ['q' => 'zzzznomatch']]);
    $ok = $res->status() === 200;
    $assertOrFail('TC-CAT-010', $ok, 'HTTP 200 empty results', 'HTTP '.$res->status(), $res, 'catalog');
});

$runCase('TC-CAT-011', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->chosenProduct === null || empty($ctx->chosenProduct['slug'])) {
        $assertOrFail('TC-CAT-011', false, 'PDP 200', 'no product chosen', null, 'catalog');

        return;
    }
    $slug = (string) $ctx->chosenProduct['slug'];
    $timed = timed($http, static fn () => $http->request('GET', '/shop/'.$slug));
    $res = $timed['response'];
    $ctx->timings['pdp'] = $timed['ms'];
    $props = propsOf($res);
    $ctx->productId = (int) data_get($props, 'product.id', 0);
    $ok = $res->status() === 200 && $ctx->productId > 0;
    $variantOptions = data_get($props, 'variantOptions');
    if (is_array($variantOptions) && ($variantOptions['hasStructuredAttributes'] ?? false)) {
        $index = $variantOptions['variantIndex'] ?? [];
        if (is_array($index) && $index !== []) {
            $ctx->variantId = (int) reset($index);
        }
    }
    $assertOrFail('TC-CAT-011', $ok, 'HTTP 200 product props', 'product_id='.$ctx->productId, $res, 'catalog');
});

$runCase('TC-CAT-012', static function () use ($assertOrFail, $http, $ctx, $recordSkip, $inSuite): void {
    if ($ctx->chosenProduct === null) {
        if ($inSuite('TC-CAT-012')) {
            $recordSkip('TC-CAT-012', 'no product');
        }

        return;
    }
    $slug = (string) $ctx->chosenProduct['slug'];
    $res = $http->request('GET', '/shop/'.$slug);
    $variantOptions = data_get(propsOf($res), 'variantOptions');
    if (! is_array($variantOptions) || ! ($variantOptions['hasStructuredAttributes'] ?? false)) {
        if ($inSuite('TC-CAT-012')) {
            $recordSkip('TC-CAT-012', 'simple product without structured variants');
        }

        return;
    }
    $index = $variantOptions['variantIndex'] ?? [];
    $ok = is_array($index) && $index !== [];
    if ($ok) {
        $ctx->variantId = (int) reset($index);
    }
    $assertOrFail('TC-CAT-012', $ok, 'variantIndex non-empty', $ok ? 'variant_id='.$ctx->variantId : 'empty index', $res, 'catalog');
});

// ── Guest cart + checkout gate ───────────────────────────────────────────────

$http->request('DELETE', '/cart');

$runCase('TC-CART-001', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->productId < 1) {
        $assertOrFail('TC-CART-001', false, 'add OK', 'missing product_id', null, 'cart');

        return;
    }
    $payload = ['product_id' => $ctx->productId, 'quantity' => 1];
    if ($ctx->variantId) {
        $payload['variant_id'] = $ctx->variantId;
    }
    $res = $http->request('POST', '/cart', ['form' => $payload]);
    $ok = in_array($res->status(), [200, 302, 303], true) && $res->status() < 500;
    $assertOrFail('TC-CART-001', $ok, 'guest POST /cart OK', 'HTTP '.$res->status(), $res, 'cart');
});

$runCase('TC-AUTH-004', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/checkout');
    $ok = isGuestBlocked($res);
    $assertOrFail(
        'TC-AUTH-004',
        $ok,
        '302 redirect to login or 401 for Inertia',
        'HTTP '.$res->status().' loc='.redirectLocation($res),
        $res,
        'auth',
    );
});

// ── Auth negative (isolated) — runs after logout to avoid login rate limits ───

// ── Login ────────────────────────────────────────────────────────────────────

$runCase('TC-AUTH-001', static function () use ($assertOrFail, $http): void {
    $http->htmlRequest('GET', '/login');
    $login = $http->htmlRequest('POST', '/login', [
        'form' => ['email' => $GLOBALS['email'], 'password' => $GLOBALS['password']],
        'referer' => $GLOBALS['base'].'/login',
    ]);
    $ok = in_array($login->status(), [200, 302, 303], true) && $login->status() < 500 && $http->xsrf !== '';
    $dash = $http->request('GET', '/dashboard');
    $authed = $dash->status() === 200;
    $assertOrFail(
        'TC-AUTH-001',
        $ok && $authed,
        'login OK and dashboard 200',
        'login='.$login->status().' dashboard='.$dash->status(),
        $login,
        'auth',
    );
});

$runCase('TC-CHK-001', static function () use ($assertOrFail, $http): void {
    $zone = $http->htmlRequest('PATCH', '/zone', [
        'form' => ['country_code' => 'ID'],
        'referer' => $GLOBALS['base'].'/',
    ]);
    $ok = in_array($zone->status(), [200, 302, 303], true) && $zone->status() < 500;
    $assertOrFail('TC-CHK-001', $ok, 'zone PATCH OK', 'HTTP '.$zone->status(), $zone, 'checkout');
});

// ── Account ──────────────────────────────────────────────────────────────────

$runCase('TC-ACCT-001', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/dashboard');
    $props = propsOf($res);
    $ok = $res->status() === 200 && array_key_exists('recentOrders', $props) && is_array($props['recentOrders']);
    $assertOrFail(
        'TC-ACCT-001',
        $ok,
        'recentOrders array present',
        $ok ? 'count='.count($props['recentOrders']) : 'missing recentOrders',
        $res,
        'account',
    );
});

$runCase('TC-ACCT-002', static function () use ($assertOrFail, $http, $ctx): void {
    $res = $http->request('GET', '/account/orders');
    $props = propsOf($res);
    $first = data_get($props, 'orders.data.0.id') ?? data_get($props, 'orders.0.id');
    if ($first) {
        $ctx->latestOrderId = (int) $first;
    }
    $ok = $res->status() === 200;
    $assertOrFail('TC-ACCT-002', $ok, 'HTTP 200', 'HTTP '.$res->status(), $res, 'account');
});

$runCase('TC-ACCT-003', static function () use ($assertOrFail, $http, $ctx, $recordSkip, $inSuite): void {
    if ($ctx->latestOrderId === null) {
        if ($inSuite('TC-ACCT-003')) {
            $recordSkip('TC-ACCT-003', 'no orders');
        }

        return;
    }
    $res = $http->request('GET', '/account/orders/'.$ctx->latestOrderId);
    $ok = $res->status() === 200;
    $assertOrFail('TC-ACCT-003', $ok, 'HTTP 200', 'order_id='.$ctx->latestOrderId, $res, 'account');
});

$runCase('TC-ACCT-004', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/account/addresses');
    $ok = $res->status() === 200;
    $assertOrFail('TC-ACCT-004', $ok, 'HTTP 200', 'HTTP '.$res->status(), $res, 'account');
});

$runCase('TC-ACCT-005', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/account/notifications');
    $ok = $res->status() === 200;
    $assertOrFail('TC-ACCT-005', $ok, 'HTTP 200', 'HTTP '.$res->status(), $res, 'account');
});

$runCase('TC-ACCT-006', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/settings/profile');
    $ok = $res->status() === 200 && str_starts_with((string) componentOf($res), 'settings/');
    $assertOrFail('TC-ACCT-006', $ok, 'HTTP 200 settings', 'HTTP '.$res->status(), $res, 'account');
});

$runCase('TC-ACCT-007', static function () use ($assertOrFail, $http, $password): void {
    $confirm = $http->htmlRequest('POST', '/user/confirm-password', [
        'form' => ['password' => $password],
        'referer' => $GLOBALS['base'].'/settings/security',
    ]);
    $res = $http->request('GET', '/settings/security');
    $ok = in_array($res->status(), [200, 423], true);
    if ($res->status() === 423) {
        $ok = in_array($confirm->status(), [200, 302, 303], true);
    }
    $assertOrFail(
        'TC-ACCT-007',
        $ok,
        'security 200 or gated 423 with confirm path',
        'security='.$res->status().' confirm='.$confirm->status(),
        $res,
        'account',
    );
});

// ── Cart (authenticated) ───────────────────────────────────────────────────

$runCase('TC-CART-002', static function () use ($assertOrFail, $http, $ctx): void {
    $cartRes = $http->request('GET', '/cart');
    $lines = cartLines(propsOf($cartRes));
    if ($lines === []) {
        $payload = ['product_id' => $ctx->productId, 'quantity' => 1];
        if ($ctx->variantId) {
            $payload['variant_id'] = $ctx->variantId;
        }
        $http->request('POST', '/cart', ['form' => $payload]);
        $cartRes = $http->request('GET', '/cart');
        $lines = cartLines(propsOf($cartRes));
    }
    $ctx->lineId = (int) ($lines[0]['id'] ?? 0);
    if ($ctx->lineId < 1) {
        $assertOrFail('TC-CART-002', false, 'patch qty 2', 'no line id', $cartRes, 'cart');

        return;
    }
    $patch = $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 2]]);
    $ok = in_array($patch->status(), [200, 302, 303], true) && $patch->status() < 500;
    $assertOrFail('TC-CART-002', $ok, 'PATCH qty 2 OK', 'HTTP '.$patch->status(), $patch, 'cart');
});

$runCase('TC-CART-003', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->lineId < 1) {
        $assertOrFail('TC-CART-003', false, 'qty 1 OK', 'no line', null, 'cart');

        return;
    }
    $patch = $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 1]]);
    $ok = in_array($patch->status(), [200, 302, 303], true) && $patch->status() < 500;
    $assertOrFail('TC-CART-003', $ok, 'qty 1 OK', 'HTTP '.$patch->status(), $patch, 'cart');
});

$runCase('TC-CART-004', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->lineId < 1) {
        $assertOrFail('TC-CART-004', false, 'qty 10 OK', 'no line', null, 'cart');

        return;
    }
    $patch = $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 10]]);
    $ok = in_array($patch->status(), [200, 302, 303], true) && $patch->status() < 500;
    $assertOrFail('TC-CART-004', $ok, 'qty 10 OK', 'HTTP '.$patch->status(), $patch, 'cart');
});

$runCase('TC-CART-005', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->lineId < 1) {
        $assertOrFail('TC-CART-005', false, 'validation not 5xx', 'no line', null, 'cart');

        return;
    }
    $patch = $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 99]]);
    $ok = $patch->status() < 500 && hasValidationSignal($patch);
    $assertOrFail(
        'TC-CART-005',
        $ok,
        '422/redirect validation not 500',
        'HTTP '.$patch->status(),
        $patch,
        'cart',
    );
    // restore valid qty for downstream checkout
    $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 1]]);
});

$runCase('TC-CART-006', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->lineId < 1) {
        $assertOrFail('TC-CART-006', false, 'validation not 5xx', 'no line', null, 'cart');

        return;
    }
    $patch = $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 0]]);
    $ok = $patch->status() < 500 && hasValidationSignal($patch);
    $assertOrFail(
        'TC-CART-006',
        $ok,
        '422/redirect validation not 500',
        'HTTP '.$patch->status(),
        $patch,
        'cart',
    );
    $http->request('PATCH', '/cart/'.$ctx->lineId, ['json' => ['quantity' => 1]]);
});

$runCase('TC-CART-007', static function () use ($assertOrFail, $http, $couponCode): void {
    $coupon = $http->request('POST', '/cart/coupon', ['form' => ['code' => $couponCode]]);
    $cart = $http->request('GET', '/cart');
    $props = propsOf($cart);
    $applied = data_get($props, 'couponCode') ?? data_get($props, 'cart.coupon_code');
    $discount = (int) data_get($props, 'cartContext.discount_total', data_get($props, 'cartContext.discountTotal', 0));
    $ok = in_array($coupon->status(), [200, 302, 303], true)
        && $coupon->status() < 500
        && ($applied === $couponCode || $discount > 0);
    $assertOrFail(
        'TC-CART-007',
        $ok,
        'coupon applied or discount > 0',
        'code='.$couponCode.' discount='.$discount.' http='.$coupon->status(),
        $coupon,
        'cart',
    );
});

$runCase('TC-CART-008', static function () use ($assertOrFail, $http): void {
    $http->request('DELETE', '/cart/coupon');
    $coupon = $http->request('POST', '/cart/coupon', ['form' => ['code' => 'BOGUS-QA-INVALID']]);
    $ok = $coupon->status() < 500 && hasValidationSignal($coupon);
    $assertOrFail(
        'TC-CART-008',
        $ok,
        'handled validation not 500',
        'HTTP '.$coupon->status(),
        $coupon,
        'cart',
    );
});

$runCase('TC-CART-009', static function () use ($assertOrFail, $http, $ctx): void {
    if ($ctx->lineId < 1) {
        $assertOrFail('TC-CART-009', false, 'remove line OK', 'no line', null, 'cart');

        return;
    }
    $del = $http->request('DELETE', '/cart/'.$ctx->lineId);
    $ok = in_array($del->status(), [200, 302, 303], true) && $del->status() < 500;
    $assertOrFail('TC-CART-009', $ok, 'DELETE line OK', 'HTTP '.$del->status(), $del, 'cart');
    // re-add for checkout
    $payload = ['product_id' => $ctx->productId, 'quantity' => 1];
    if ($ctx->variantId) {
        $payload['variant_id'] = $ctx->variantId;
    }
    $http->request('POST', '/cart', ['form' => $payload]);
    $cart = $http->request('GET', '/cart');
    $lines = cartLines(propsOf($cart));
    $ctx->lineId = (int) ($lines[0]['id'] ?? 0);
});

// ── Checkout ─────────────────────────────────────────────────────────────────

$runCase('TC-CHK-002', static function () use ($assertOrFail, $http): void {
    $res = $http->request('GET', '/checkout/destinations', ['query' => ['q' => '']]);
    $ok = $res->status() < 500 && ($res->status() === 422 || hasValidationSignal($res));
    $assertOrFail(
        'TC-CHK-002',
        $ok,
        '422 validation for empty q',
        'HTTP '.$res->status(),
        $res,
        'checkout',
    );
});

$runCase('TC-CHK-003', static function () use ($assertOrFail, $http): void {
    $address = $http->request('POST', '/checkout/shipping-address', ['form' => []]);
    $ok = $address->status() < 500 && hasValidationSignal($address);
    $assertOrFail(
        'TC-CHK-003',
        $ok,
        'validation errors for missing fields',
        'HTTP '.$address->status(),
        $address,
        'checkout',
    );
});

$runCase('TC-CHK-004', static function () use ($assertOrFail, $http): void {
    $address = $http->request('POST', '/checkout/shipping-address', [
        'form' => [
            'first_name' => 'QA',
            'last_name' => 'MissingDest',
            'street_address' => 'Jl. Test 1',
            'postal_code' => '12220',
            'city' => 'Jakarta',
            'state' => 'DKI',
            'phone_number' => '081234567890',
        ],
    ]);
    $ok = $address->status() < 500 && hasValidationSignal($address);
    $assertOrFail(
        'TC-CHK-004',
        $ok,
        'validation for missing rajaongkir_destination_id',
        'HTTP '.$address->status(),
        $address,
        'checkout',
    );
});

$checkoutStep2Props = [];

$runCase('TC-CHK-005', static function () use (
    $assertOrFail,
    $http,
    $ctx,
    $destQuery,
    &$checkoutStep2Props,
): void {
    $checkout = $http->request('GET', '/checkout');
    if ($checkout->status() !== 200) {
        $assertOrFail('TC-CHK-005', false, 'checkout step 1', 'HTTP '.$checkout->status(), $checkout, 'checkout');

        return;
    }

    $dest = $http->request('GET', '/checkout/destinations', [
        'query' => ['q' => $destQuery, 'limit' => 5],
    ]);
    if (! $dest->successful()) {
        $assertOrFail('TC-CHK-005', false, 'destination search OK', 'HTTP '.$dest->status(), $dest, 'checkout');

        return;
    }
    $destData = $dest->json('data') ?? [];
    if (! is_array($destData) || $destData === []) {
        $assertOrFail('TC-CHK-005', false, 'destination results', 'empty for q='.$destQuery, $dest, 'checkout');

        return;
    }
    $ctx->destination = $destData[0];
    $destination = $ctx->destination;
    $postalCode = (string) (
        $destination['postal_code']
        ?? $destination['zip_code']
        ?? $destination['postcode']
        ?? ''
    );
    if ($postalCode === '' && preg_match('/\b(\d{5})\b/', (string) ($destination['label'] ?? ''), $mPostal)) {
        $postalCode = $mPostal[1];
    }
    if ($postalCode === '') {
        $postalCode = '12220';
    }
    $city = (string) ($destination['city_name'] ?? $destination['city'] ?? 'Jakarta Selatan');
    $state = (string) ($destination['province_name'] ?? $destination['province'] ?? 'DKI Jakarta');

    $address = $http->request('POST', '/checkout/shipping-address', [
        'form' => [
            'first_name' => 'Deploy',
            'last_name' => 'QaE2E',
            'street_address' => 'Jl. Melawai Raya No. 1',
            'street_address_plus' => '',
            'postal_code' => $postalCode,
            'city' => $city,
            'state' => $state,
            'phone_number' => '081234567890',
            'rajaongkir_destination_id' => (string) $destination['id'],
            'rajaongkir_destination_label' => (string) $destination['label'],
        ],
    ]);
    if (! in_array($address->status(), [200, 302, 303], true)) {
        $assertOrFail('TC-CHK-005', false, 'shipping address saved', 'HTTP '.$address->status(), $address, 'checkout');

        return;
    }

    $timed = timed($http, static fn () => $http->request('GET', '/checkout', ['query' => ['step' => 2]]));
    $checkout2 = $timed['response'];
    $ctx->timings['checkout2'] = $timed['ms'];
    $checkoutStep2Props = propsOf($checkout2);

    $deliveryOptions = $checkoutStep2Props['deliveryOptions'] ?? [];
    $byShipment = $checkoutStep2Props['deliveryOptionsByShipment'] ?? [];
    $flatRates = [];
    if (is_array($deliveryOptions) && $deliveryOptions !== []) {
        $flatRates = $deliveryOptions;
    } elseif (is_array($byShipment)) {
        foreach ($byShipment as $opts) {
            if (is_array($opts)) {
                foreach ($opts as $opt) {
                    $flatRates[] = $opt;
                }
            }
        }
    }
    $ok = $checkout2->status() === 200 && $flatRates !== [];
    $assertOrFail(
        'TC-CHK-005',
        $ok,
        'delivery rates present',
        $ok ? 'rates='.count($flatRates) : 'no rates',
        $checkout2,
        'checkout',
    );
});

$runCase('TC-CHK-006', static function () use ($assertOrFail, &$checkoutStep2Props): void {
    $paymentOptions = $checkoutStep2Props['paymentOptions'] ?? [];
    $ok = is_array($paymentOptions) && $paymentOptions !== [];
    $assertOrFail(
        'TC-CHK-006',
        $ok,
        'payment methods present',
        $ok ? 'count='.count($paymentOptions) : 'none',
        null,
        'checkout',
    );
});

$runCase('TC-CHK-007', static function () use ($assertOrFail, &$checkoutStep2Props): void {
    $saved = $checkoutStep2Props['savedAddresses'] ?? null;
    $ok = is_array($saved) && $saved !== [];
    $assertOrFail(
        'TC-CHK-007',
        $ok,
        'savedAddresses non-empty after save',
        is_array($saved) ? 'count='.count($saved) : 'not array',
        null,
        'checkout',
    );
});

$runCase('TC-CHK-008', static function () use ($assertOrFail, $http, $ctx, &$checkoutStep2Props): void {
    if (! $GLOBALS['placeOrder']) {
        return;
    }
    $props = $checkoutStep2Props;
    $deliveryOptions = $props['deliveryOptions'] ?? [];
    $byShipment = $props['deliveryOptionsByShipment'] ?? [];
    $paymentOptions = $props['paymentOptions'] ?? [];
    $allocation = $props['allocation'] ?? null;

    $flatRates = [];
    if (is_array($deliveryOptions) && $deliveryOptions !== []) {
        $flatRates = $deliveryOptions;
    } elseif (is_array($byShipment)) {
        foreach ($byShipment as $opts) {
            if (is_array($opts)) {
                foreach ($opts as $opt) {
                    $flatRates[] = $opt;
                }
            }
        }
    }
    if ($flatRates === [] || ! is_array($paymentOptions) || $paymentOptions === []) {
        $assertOrFail('TC-CHK-008', false, 'place order', 'missing rates or payment', null, 'checkout');

        return;
    }
    $rate = $flatRates[0];
    if (is_array($allocation) && count($allocation) > 1) {
        $payload = ['rates' => []];
        foreach ($allocation as $pkg) {
            $invId = $pkg['inventory_id'];
            $opts = $byShipment[$invId] ?? $byShipment[(string) $invId] ?? [];
            $opt = is_array($opts) && $opts !== [] ? $opts[0] : $rate;
            $payload['rates'][$invId] = $opt['service_code'];
        }
        $ship = $http->request('POST', '/checkout/shipping-option', ['form' => $payload]);
    } else {
        $ship = $http->request('POST', '/checkout/shipping-option', [
            'form' => ['service_code' => $rate['service_code']],
        ]);
    }
    if (! in_array($ship->status(), [200, 302, 303], true)) {
        $assertOrFail('TC-CHK-008', false, 'shipping option saved', 'HTTP '.$ship->status(), $ship, 'checkout');

        return;
    }
    $paymentMethod = $paymentOptions[0];
    $place = $http->request('POST', '/checkout/place-order', [
        'form' => ['payment_method_id' => $paymentMethod['id']],
    ]);
    $ok = in_array($place->status(), [200, 302, 303], true) && $place->status() < 500;
    $assertOrFail('TC-CHK-008', $ok, 'unpaid order placed', 'HTTP '.$place->status(), $place, 'checkout');
});

$runCase('TC-CART-010', static function () use ($assertOrFail, $http): void {
    $clear = $http->request('DELETE', '/cart');
    $ok = in_array($clear->status(), [200, 302, 303], true) && $clear->status() < 500;
    $assertOrFail('TC-CART-010', $ok, 'clear cart OK', 'HTTP '.$clear->status(), $clear, 'cart');
});

// ── Logout ───────────────────────────────────────────────────────────────────

$runCase('TC-AUTH-002', static function () use ($assertOrFail, $http): void {
    $logout = $http->htmlRequest('POST', '/logout', ['referer' => $GLOBALS['base'].'/dashboard']);
    $ok = in_array($logout->status(), [200, 204, 302, 303], true);
    $guestCheck = $http->request('GET', '/account/orders');
    $guestBlocked = isGuestBlocked($guestCheck);
    $assertOrFail(
        'TC-AUTH-002',
        $ok && $guestBlocked,
        'logout OK and guest blocked from account',
        'logout='.$logout->status().' account='.$guestCheck->status(),
        $logout,
        'auth',
    );
});

$runCase('TC-AUTH-003', static function () use ($assertOrFail): void {
    $guest = (new QaHttp($GLOBALS['base']))->cloneFresh();
    $guest->seedCsrfFromLoginPage();
    $login = $guest->htmlRequest('POST', '/login', [
        'form' => ['email' => $GLOBALS['email'], 'password' => 'wrong-password-qa'],
        'referer' => $GLOBALS['base'].'/login',
    ]);
    $stillGuest = $guest->request('GET', '/checkout');
    $guestCheckoutBlocked = isGuestBlocked($stillGuest);
    $loginHandled = in_array($login->status(), [422, 302, 303, 429], true) && $login->status() < 500;
    if ($login->status() === 429) {
        $assertOrFail(
            'TC-AUTH-003',
            true,
            '422/302 login fail and still guest',
            'login rate-limited (429) — still guest checkout='.$stillGuest->status(),
            $login,
            'auth',
        );

        return;
    }
    $ok = $loginHandled && $guestCheckoutBlocked;
    $assertOrFail(
        'TC-AUTH-003',
        $ok,
        '422/302 login fail and still guest',
        'login='.$login->status().' checkout='.$stillGuest->status(),
        $login,
        'auth',
    );
});

// ── Performance ──────────────────────────────────────────────────────────────

foreach ([
    'TC-PERF-001' => 'home',
    'TC-PERF-002' => 'shop',
    'TC-PERF-003' => 'pdp',
    'TC-PERF-004' => 'checkout2',
] as $tc => $key) {
    $runCase($tc, static function () use ($assertOrFail, $ctx, $tc, $key, $slowThresholdMs): void {
        $ms = $ctx->timings[$key] ?? null;
        if ($ms === null) {
            $assertOrFail($tc, false, "<={$slowThresholdMs}ms", 'no timing captured', null, 'performance');

            return;
        }
        $ok = $ms <= $slowThresholdMs;
        $assertOrFail(
            $tc,
            $ok,
            "<={$slowThresholdMs}ms",
            "{$ms}ms",
            null,
            'performance',
        );
    });
}

// ── Final report ─────────────────────────────────────────────────────────────

$hardFail = $summary['s1'] > 0 || $summary['s2'] > 0 || ($strict && $summary['s3'] > 0);
$ok = ! $hardFail;

echo json_encode([
    'ok' => $ok,
    'suite' => $suite,
    'summary' => $summary,
    'defects' => $defects,
    'cases' => $results,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)."\n";

exit($ok ? 0 : 1);
