# Task 1 Report: Config + HTTP clients (scaffolding)

## What I implemented

- Added Komerce/RajaOngkir configuration in `config/komerce.php`.
- Added `.env.example` placeholders for:
  - `KOMERCE_API_KEY`
  - `KOMERCE_PAYMENT_BASE_URL`
  - `RAJAONGKIR_COST_BASE_URL`
  - `RAJAONGKIR_DELIVERY_BASE_URL`
  - `KOMERCE_WEBHOOK_SECRET`
  - `KOMERCE_TIMEOUT`
- Preserved `PAYMENT_STRIPE_ENABLED=false` in `.env.example`.
- Added shared HTTP helpers in `App\Services\Komerce\Concerns\UsesKomerceHttp`.
  - Payment client uses `x-api-key`.
  - Shipping cost client uses `key`.
  - Base URLs are normalized and timeout is read from config.
- Added `App\Services\Komerce\PaymentClient`.
  - `createVirtualAccount(array $payload): array`
  - `createQris(array $payload): array`
  - `getStatus(string $reference): array`
- Added `App\Services\Komerce\ShippingCostClient`.
  - `calculate(array $origin, array $destination, int $weightGrams, array $couriers): array`
  - Sends form payload with `origin`, `destination`, `weight`, and colon-joined `courier`.

## What I tested and test results

- `php artisan test --filter=PaymentClientTest`
  - Result: passed, 3 tests, 6 assertions.
- `php artisan test --filter=ShippingCostClientTest`
  - Result: passed, 1 test, 2 assertions.
- `php artisan test --filter=Komerce`
  - Result: passed, 4 tests, 8 assertions.
- `./vendor/bin/pint --test "app/Services/Komerce" "config/komerce.php" "tests/Unit/Services/Komerce"`
  - Result: passed.

## TDD Evidence

### RED

Command:

```bash
php artisan test --filter=PaymentClientTest
```

Relevant failing output:

```text
{"tool":"phpunit","result":"failed","tests":3,"passed":0,"assertions":0,"duration_ms":246,"errors":3,"error_details":[{"test":"Tests\\Unit\\Services\\Komerce\\PaymentClientTest::test_create_virtual_account_posts_json_payload_with_api_key_header","file":"/workspace/tests/Unit/Services/Komerce/PaymentClientTest.php","line":14,"message":"Class \"App\\Services\\Komerce\\PaymentClient\" not found"},{"test":"Tests\\Unit\\Services\\Komerce\\PaymentClientTest::test_create_qris_posts_json_payload_with_qris_payment_type","file":"/workspace/tests/Unit/Services/Komerce/PaymentClientTest.php","line":56,"message":"Class \"App\\Services\\Komerce\\PaymentClient\" not found"},{"test":"Tests\\Unit\\Services\\Komerce\\PaymentClientTest::test_get_status_fetches_payment_status_by_reference","file":"/workspace/tests/Unit/Services/Komerce/PaymentClientTest.php","line":90,"message":"Class \"App\\Services\\Komerce\\PaymentClient\" not found"}]}
```

Why expected:

- The tests were written before creating `App\Services\Komerce\PaymentClient`, so the missing class failure proved the Payment client tests were exercising new behavior.

Additional RED refinement:

```bash
php artisan test --filter=PaymentClientTest
```

Relevant failing output:

```text
{"tool":"phpunit","result":"failed","tests":3,"passed":2,"assertions":6,"duration_ms":206,"failed":1,"failures":[{"test":"Tests\\Unit\\Services\\Komerce\\PaymentClientTest::test_create_qris_posts_json_payload_with_qris_payment_type","file":"/workspace/tests/Unit/Services/Komerce/PaymentClientTest.php","line":56,"message":"An expected request was not recorded.\nFailed asserting that false is true."}]}
```

Why expected:

- The QRIS test supplied `channel_code` but expected the outbound QRIS body to omit it, matching the API notes. The client was then updated to unset `channel_code` for QRIS.

### GREEN

Command:

```bash
php artisan test --filter=PaymentClientTest
```

Relevant passing output:

```text
{"tool":"phpunit","result":"passed","tests":3,"passed":3,"assertions":6,"duration_ms":209}
```

Command:

```bash
php artisan test --filter=Komerce
```

Relevant passing output:

```text
{"tool":"phpunit","result":"passed","tests":4,"passed":4,"assertions":8,"duration_ms":249}
```

Command:

```bash
./vendor/bin/pint --test "app/Services/Komerce" "config/komerce.php" "tests/Unit/Services/Komerce"
```

Relevant passing output:

```text
{"tool":"pint","result":"passed"}
```

## Files changed

- `.env.example`
- `config/komerce.php`
- `app/Services/Komerce/Concerns/UsesKomerceHttp.php`
- `app/Services/Komerce/PaymentClient.php`
- `app/Services/Komerce/ShippingCostClient.php`
- `tests/Unit/Services/Komerce/PaymentClientTest.php`
- `tests/Unit/Services/Komerce/ShippingCostClientTest.php`
- `.superpowers/sdd/task-1-report.md`

## Self-review findings

- Confirmed payment auth uses `x-api-key`, not Bearer.
- Confirmed shipping cost auth uses `key`, not Bearer.
- Confirmed `ShippingCostClient::calculate` uses `int $weightGrams`.
- Confirmed `.env.example` keeps `PAYMENT_STRIPE_ENABLED=false`.
- Confirmed no real API keys or `.env` changes were introduced.
- Confirmed QRIS requests strip `channel_code`.
- Confirmed touched PHP files pass Pint check.

## Any issues or concerns

- None.
