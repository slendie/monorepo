<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Slendie'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'pt'),

    'key' => env('APP_KEY'),

    /* TrimStrings: '' vira null no input. */
    'convert_empty_strings_to_null' => true,

    /* Cabeçalhos de segurança (SecurityHeaders). Um valor vazio desactiva o cabeçalho. */
    'security_headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ],

    /* Providers da aplicação (os da framework são registados automaticamente). */
    'providers' => [
        // App\Providers\AppServiceProvider::class,
    ],
];
