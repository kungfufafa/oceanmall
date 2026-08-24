<?php

declare(strict_types=1);

$komerceEnabled = env('KOMERCE_ENABLED');

/*
 * Komerce issues independent credentials for Payment, Shipping Cost, Shipping
 * Delivery, and QRISLY. A key from one product must never authenticate another
 * product, so every integration reads only its dedicated environment variable.
 */
$paymentApiKey = trim((string) env('KOMERCE_PAYMENT_API_KEY', ''));
$shippingCostApiKey = trim((string) env('KOMERCE_SHIPPING_COST_API_KEY', ''));
$shippingDeliveryApiKey = trim((string) env('KOMERCE_SHIPPING_DELIVERY_API_KEY', ''));
/*
 * QRISLY is opt-in: leave KOMERCE_QRISLY_API_KEY empty to skip the QRISLY product
 * and keep using Payment API QRIS instead. Do not fall back to the Payment key.
 */
$qrislyApiKey = trim((string) env('KOMERCE_QRISLY_API_KEY', ''));

return [
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
     * Leave KOMERCE_ENABLED unset to auto-detect: each service is considered
     * ready only when its own required credentials are filled in. Set
     * KOMERCE_ENABLED=false to force it off even when keys are present
     * (mirrors PAYMENT_STRIPE_ENABLED). `null` here means "auto-detect from
     * service credentials" (resolved by the helpers in app/helpers.php).
     */
    'enabled' => $komerceEnabled === null
        ? null
        : filter_var($komerceEnabled, FILTER_VALIDATE_BOOLEAN),

    'payment_base_url' => env('KOMERCE_PAYMENT_BASE_URL', 'https://api-sandbox.collaborator.komerce.id/user'),

    'rajaongkir' => [
        'cost_base_url' => env('RAJAONGKIR_COST_BASE_URL', 'https://rajaongkir.komerce.id'),
        'delivery_base_url' => env('RAJAONGKIR_DELIVERY_BASE_URL', 'https://api-sandbox.collaborator.komerce.id'),
    ],

    'couriers' => array_values(array_filter(array_map('trim', explode(',', env('RAJAONGKIR_COURIERS', 'jne,jnt,sicepat,ide,anteraja,pos,tiki,lion,ninja,wahana,rpx,ncs'))))),

    'webhook_secret' => env('KOMERCE_WEBHOOK_SECRET', ''),

    'pickup_vehicle' => env('KOMERCE_PICKUP_VEHICLE', 'Motor'),

    'pickup_time' => env('KOMERCE_PICKUP_TIME', '10:00:00'),

    'timeout' => (int) env('KOMERCE_TIMEOUT', 30),

    /*
     * Payment API VA expiry_duration in seconds. Official create example uses
     * 86400; documented VA minimum is 3600. QRIS Payment expiry is provider-fixed.
     */
    'payment_expiry_duration' => max(3600, (int) env('KOMERCE_PAYMENT_EXPIRY_DURATION', 86400)),
];
