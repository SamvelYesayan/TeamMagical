<?php

return [
    'name' => env('APP_NAME', 'Magical'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    // All timestamps are stored and serialised in UTC; the SPA renders them in Asia/Yerevan.
    'timezone' => 'UTC',
    'locale' => env('APP_LOCALE', 'hy'),
    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'hy'),
    'faker_locale' => 'en_US',
    'cipher' => 'AES-256-CBC',
    'key' => env('APP_KEY'),
    'previous_keys' => [
        ...array_filter(explode(',', (string) env('APP_PREVIOUS_KEYS', ''))),
    ],
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
