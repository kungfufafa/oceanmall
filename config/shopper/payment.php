<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Payment Drivers
    |--------------------------------------------------------------------------
    |
    | Configure payment provider drivers. Each driver connects to a payment
    | gateway API (Stripe, PayPal, etc.) for processing payments,
    | captures, and refunds.
    |
    | Credentials should be stored in your .env file, never in the database.
    |
    */

    'drivers' => [

        'stripe' => [
            'enabled' => env('PAYMENT_STRIPE_ENABLED', false),
            'sandbox' => env('PAYMENT_SANDBOX', false),
            'credentials' => [
                'secret_key' => env('STRIPE_SECRET_KEY'),
                'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
                'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
            ],
        ],

        'paypal' => [
            'enabled' => env('PAYMENT_PAYPAL_ENABLED', false),
            'sandbox' => env('PAYMENT_SANDBOX', false),
            'credentials' => [
                'client_id' => env('PAYPAL_CLIENT_ID'),
                'client_secret' => env('PAYPAL_CLIENT_SECRET'),
                'webhook_id' => env('PAYPAL_WEBHOOK_ID'),
            ],
        ],

        /*
         | Komerce Payment API (VA / QRIS) + optional QRISLY.
         | Credentials stay in config/komerce.php. Enablement is also set at
         | runtime in AppServiceProvider from the dedicated product keys.
         */
        'komerce' => [
            'enabled' => env('KOMERCE_PAYMENT_API_KEY') !== null
                && env('KOMERCE_PAYMENT_API_KEY') !== '',
            'sandbox' => true,
            'credentials' => [
                'api_key' => env('KOMERCE_PAYMENT_API_KEY'),
                'webhook_secret' => env('KOMERCE_WEBHOOK_SECRET'),
                'base_url' => env('KOMERCE_PAYMENT_BASE_URL', 'https://api-sandbox.collaborator.komerce.id/user'),
            ],
        ],

    ],

];
