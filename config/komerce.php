<?php

declare(strict_types=1);

$komerceEnabled = env('KOMERCE_ENABLED');

return [
    'api_key' => env('KOMERCE_API_KEY', ''),

    /*
     * Master switch for the Komerce collaborator integration (payment + RajaOngkir).
     * Leave KOMERCE_ENABLED unset to auto-detect: the integration is considered
     * enabled only when KOMERCE_API_KEY is filled in. Set KOMERCE_ENABLED=false
     * to force it off even when a key is present (mirrors PAYMENT_STRIPE_ENABLED).
     * `null` here means "auto-detect from the API key" (resolved by komerce_enabled()).
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

    'timeout' => (int) env('KOMERCE_TIMEOUT', 30),
];
