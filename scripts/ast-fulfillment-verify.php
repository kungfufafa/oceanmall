<?php

declare(strict_types=1);

/**
 * AST verification of the paid → RajaOngkir AWB path.
 * Parses PHP sources (not runtime) and asserts the call graph still exists.
 */

require __DIR__.'/../vendor/autoload.php';

use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use PhpParser\PhpVersion;

$root = dirname(__DIR__);
$parser = (new ParserFactory)->createForVersion(PhpVersion::fromComponents(8, 4));
$finder = new NodeFinder;

$files = [
    'webhook_payment' => 'app/Http/Controllers/Webhooks/KomercePaymentWebhookController.php',
    'webhook_qrisly' => 'app/Http/Controllers/Webhooks/KomerceQrislyWebhookController.php',
    'webhook_delivery' => 'app/Http/Controllers/Webhooks/KomerceDeliveryWebhookController.php',
    'mark_paid' => 'app/Actions/Checkout/MarkOrderPaidFromKomerce.php',
    'dispatch' => 'app/Actions/Shipping/DispatchRajaOngkirDelivery.php',
    'job' => 'app/Jobs/CreateRajaOngkirDeliveryForShipment.php',
    'driver' => 'app/Shipping/Drivers/KomerceShippingDriver.php',
    'delivery_client' => 'app/Services/Komerce/ShippingDeliveryClient.php',
    'print_labels' => 'app/Actions/Shipping/PrintShipmentLabels.php',
    'sync_shopper' => 'app/Actions/Shipping/SyncOrderShippingFromShipments.php',
    'issue' => 'app/Actions/Shipping/IssueRajaOngkirFulfillment.php',
    'fulfillment_ui' => 'app/Livewire/Shopper/OrderFulfillment.php',
    'schedule' => 'routes/console.php',
    'routes' => 'routes/web.php',
];

/**
 * @return array{calls: list<string>, names: list<string>, strings: list<string>}
 */
function astIndex(Parser $parser, NodeFinder $finder, string $path): array
{
    $code = file_get_contents($path);
    if ($code === false) {
        throw new RuntimeException("Cannot read {$path}");
    }

    $ast = $parser->parse($code);
    if ($ast === null) {
        throw new RuntimeException("Cannot parse {$path}");
    }

    $calls = [];
    foreach ($finder->findInstanceOf($ast, Node\Expr\MethodCall::class) as $node) {
        if ($node->name instanceof Node\Identifier) {
            $calls[] = $node->name->toString();
        }
    }
    foreach ($finder->findInstanceOf($ast, Node\Expr\StaticCall::class) as $node) {
        $class = $node->class instanceof Node\Name ? $node->class->toString() : '';
        $method = $node->name instanceof Node\Identifier ? $node->name->toString() : '';
        $calls[] = $class === '' ? $method : "{$class}::{$method}";
    }
    foreach ($finder->findInstanceOf($ast, Node\Expr\FuncCall::class) as $node) {
        if ($node->name instanceof Node\Name) {
            $calls[] = $node->name->toString();
        }
    }
    foreach ($finder->findInstanceOf($ast, Node\Expr\New_::class) as $node) {
        if ($node->class instanceof Node\Name) {
            $calls[] = 'new '.$node->class->toString();
        }
    }

    $names = [];
    foreach ($finder->findInstanceOf($ast, Node\Stmt\ClassMethod::class) as $node) {
        $names[] = $node->name->toString();
    }

    $strings = [];
    foreach ($finder->findInstanceOf($ast, Node\Scalar\String_::class) as $node) {
        $strings[] = $node->value;
    }

    $classes = [];
    foreach ($finder->findInstanceOf($ast, Node\Expr\ClassConstFetch::class) as $node) {
        if ($node->class instanceof Node\Name) {
            $classes[] = $node->class->toString();
        }
    }
    foreach ($finder->findInstanceOf($ast, Node\Name::class) as $node) {
        $classes[] = $node->toString();
    }

    return [
        'calls' => array_values(array_unique($calls)),
        'names' => $names,
        'strings' => $strings,
        'classes' => array_values(array_unique($classes)),
    ];
}

$index = [];
foreach ($files as $key => $relative) {
    $path = $root.'/'.$relative;
    $index[$key] = astIndex($parser, $finder, $path);
}

$failures = [];

$expectCall = static function (string $file, string $needle, string $why) use ($index, &$failures): void {
    $haystack = $index[$file]['calls'] ?? [];
    $hit = false;
    foreach ($haystack as $call) {
        if ($call === $needle || str_ends_with($call, $needle) || str_contains($call, $needle)) {
            $hit = true;
            break;
        }
    }
    if (! $hit) {
        $failures[] = "[{$file}] missing call `{$needle}` — {$why}";
    }
};

$expectMethod = static function (string $file, string $method, string $why) use ($index, &$failures): void {
    if (! in_array($method, $index[$file]['names'] ?? [], true)) {
        $failures[] = "[{$file}] missing method `{$method}()` — {$why}";
    }
};

$expectString = static function (string $file, string $needle, string $why) use ($index, &$failures): void {
    $hit = false;
    foreach ($index[$file]['strings'] ?? [] as $value) {
        if ($value === $needle || str_contains($value, $needle)) {
            $hit = true;
            break;
        }
    }
    if (! $hit) {
        $failures[] = "[{$file}] missing string `{$needle}` — {$why}";
    }
};

$expectClass = static function (string $file, string $needle, string $why) use ($index, &$failures): void {
    $hit = false;
    foreach ($index[$file]['classes'] ?? [] as $value) {
        if ($value === $needle || str_ends_with($value, $needle)) {
            $hit = true;
            break;
        }
    }
    if (! $hit) {
        $failures[] = "[{$file}] missing class `{$needle}` — {$why}";
    }
};

$expectCall('webhook_payment', 'handleWebhook', 'Payment webhook must verify HMAC via Komerce driver');
$expectCall('webhook_payment', 'handle', 'Payment webhook must mark the order paid');
$expectCall('webhook_qrisly', 'handle', 'QRISLY webhook must mark the order paid');
$expectCall('mark_paid', 'retrievePayment', 'Paid transition must confirm remote PAID status');
$expectClass('mark_paid', 'DispatchRajaOngkirDelivery', 'Paid transition must dispatch RajaOngkir delivery');
$expectMethod('dispatch', 'handle', 'Dispatcher must exist');
$expectMethod('dispatch', 'dispatchOne', 'Dispatcher must send per-shipment work');
$expectCall('dispatch', 'terminating', 'HTTP path must run AWB after the webhook 200, not wait on unique queue lock');
$expectCall('dispatch', 'dispatch', 'Failed HTTP attempt must fall back to the queue');
$expectCall('dispatch', 'afterCommit', 'Must not create AWB before payment commit');
$expectCall('job', 'createShipment', 'Job must go through the Shopper komerce shipping driver');
$expectCall('job', 'storeOrder', 'Job must call official store-order');
$expectCall('job', 'requestPickup', 'Job must call official pickup to obtain AWB');
$expectCall('job', 'printOfficialLabel', 'Job must fetch official print-label after pickup');
$expectClass('job', 'SyncOrderShippingFromShipments', 'Job must record AWB on Shopper OrderShipping');
$expectMethod('delivery_client', 'storeOrder', 'Official store-order client method');
$expectMethod('delivery_client', 'requestPickup', 'Official pickup client method');
$expectMethod('delivery_client', 'printLabel', 'Official print-label client method');
$expectString('delivery_client', '/order/api/v1/orders/store', 'Store-order path');
$expectString('delivery_client', '/order/api/v1/pickup/request', 'Pickup path');
$expectString('delivery_client', '/order/api/v1/orders/print-label', 'Print-label path');
$expectString('routes', 'webhooks/komerce/payment', 'Payment webhook route');
$expectString('routes', 'webhooks/komerce/qrisly', 'QRISLY webhook route');
$expectString('routes', 'webhooks/komerce/delivery', 'Delivery webhook route');
$expectString('schedule', 'komerce:fulfill-paid-orders', 'Scheduler must retry paid orders without AWB');
$expectClass('issue', 'CreateRajaOngkirDeliveryForShipment', 'Admin issue path must use the same job');
$expectClass('fulfillment_ui', 'IssueRajaOngkirFulfillment', 'Shopper label button must not open CRUD');
$expectCall('print_labels', 'printLabels', 'Print action must use Komerce driver labels');
$expectCall('sync_shopper', 'updateOrCreate', 'Shopper must record tracking, not invent it');
$expectCall('webhook_delivery', 'handle', 'Delivery webhook must apply tracking');

fwrite(STDOUT, 'AST files parsed: '.count($index).PHP_EOL);
foreach ($files as $key => $relative) {
    fwrite(STDOUT, sprintf(
        "  %-18s methods=%-2d calls=%-3d  %s\n",
        $key,
        count($index[$key]['names']),
        count($index[$key]['calls']),
        $relative,
    ));
}

if ($failures !== []) {
    fwrite(STDERR, PHP_EOL.'AST FAILURES:'.PHP_EOL);
    foreach ($failures as $failure) {
        fwrite(STDERR, '  - '.$failure.PHP_EOL);
    }
    exit(1);
}

fwrite(STDOUT, PHP_EOL.'AST OK: paid webhook → mark paid → dispatch after commit → store-order + pickup + print-label → Shopper record.'.PHP_EOL);
exit(0);
