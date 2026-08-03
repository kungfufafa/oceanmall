<?php

declare(strict_types=1);

return [
    'api_key' => env('KOMERCE_API_KEY', ''),

    'payment_base_url' => env('KOMERCE_PAYMENT_BASE_URL', 'https://api-sandbox.collaborator.komerce.id/user'),

    'rajaongkir' => [
        'cost_base_url' => env('RAJAONGKIR_COST_BASE_URL', 'https://rajaongkir.komerce.id'),
        'delivery_base_url' => env('RAJAONGKIR_DELIVERY_BASE_URL', ''),
    ],

    'couriers' => array_values(array_filter(array_map('trim', explode(',', env('RAJAONGKIR_COURIERS', 'jne,jnt,sicepat'))))),

    'webhook_secret' => env('KOMERCE_WEBHOOK_SECRET', ''),

    'timeout' => (int) env('KOMERCE_TIMEOUT', 30),
];
