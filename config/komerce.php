<?php

declare(strict_types=1);

$komerceEnabled = env('KOMERCE_ENABLED');

/*
 * Per-service keys mirror https://collaborator.komerce.id/settings (Api Key List).
 * KOMERCE_API_KEY remains as a legacy single-key fallback for local/tests.
 * Empty dedicated keys fall through to the legacy key (or Payment for QRISLY).
 */
$legacyApiKey = trim((string) env('KOMERCE_API_KEY', ''));
$paymentApiKey = trim((string) env('KOMERCE_PAYMENT_API_KEY', '')) ?: $legacyApiKey;
$shippingCostApiKey = trim((string) env('KOMERCE_SHIPPING_COST_API_KEY', '')) ?: $legacyApiKey;
$shippingDeliveryApiKey = trim((string) env('KOMERCE_SHIPPING_DELIVERY_API_KEY', '')) ?: $legacyApiKey;
/*
 * QRISLY is opt-in: leave KOMERCE_QRISLY_API_KEY empty to skip the QRISLY product
 * and keep using Payment API QRIS instead. Do not fall back to the Payment key.
 */
$qrislyApiKey = trim((string) env('KOMERCE_QRISLY_API_KEY', ''));

return [
    'api_key' => $legacyApiKey,

    'payment_api_key' => $paymentApiKey,

    'shipping_cost_api_key' => $shippingCostApiKey,

    'shipping_delivery_api_key' => $shippingDeliveryApiKey,

    'qrisly_api_key' => $qrislyApiKey,

    /*
     * Merchant QRIS template id from Collaborator upload-qris.
     * Required together with qrisly_api_key for qrisly_enabled().
     */
    'qrisly_qris_id' => trim((string) env('KOMERCE_QRISLY_QRIS_ID', '')),

    'qrisly_base_url' => env(
        'KOMERCE_QRISLY_BASE_URL',
        env('KOMERCE_PAYMENT_BASE_URL', 'https://api-sandbox.collaborator.komerce.id/user'),
    ),

    'qrisly_unique_amount' => filter_var(env('KOMERCE_QRISLY_UNIQUE_AMOUNT', true), FILTER_VALIDATE_BOOLEAN),

    /*
     * Master switch for the Komerce collaborator integration (payment + RajaOngkir).
     * Leave KOMERCE_ENABLED unset to auto-detect: the integration is considered
     * enabled only when at least one service API key is filled in. Set
     * KOMERCE_ENABLED=false to force it off even when keys are present
     * (mirrors PAYMENT_STRIPE_ENABLED). `null` here means "auto-detect from
     * the API keys" (resolved by komerce_enabled()).
     */
    'enabled' => $komerceEnabled === null
        ? null
        : filter_var($komerceEnabled, FILTER_VALIDATE_BOOLEAN),

    'payment_base_url' => env('KOMERCE_PAYMENT_BASE_URL', 'https://api-sandbox.collaborator.komerce.id/user'),

    'rajaongkir' => [
        'cost_base_url' => env('RAJAONGKIR_COST_BASE_URL', 'https://rajaongkir.komerce.id'),
        'delivery_base_url' => env('RAJAONGKIR_DELIVERY_BASE_URL', 'https://api-sandbox.collaborator.komerce.id'),
    ],

    'couriers' => array_values(array_filter(array_map('trim', explode(',', env('RAJAONGKIR_COURIERS', 'jne,jnt,sicepat'))))),

    'webhook_secret' => env('KOMERCE_WEBHOOK_SECRET', ''),

    'pickup_vehicle' => env('KOMERCE_PICKUP_VEHICLE', 'Motor'),

    'pickup_time' => env('KOMERCE_PICKUP_TIME', '10:00'),

    'timeout' => (int) env('KOMERCE_TIMEOUT', 30),
];
