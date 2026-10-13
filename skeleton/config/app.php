<?php

declare(strict_types=1);

return [
    'name' => env('APP_NAME', 'Slendie'),

    'env' => env('APP_ENV', 'production'),

    'debug' => (bool) env('APP_DEBUG', false),

    'url' => env('APP_URL', 'http://localhost'),

    'timezone' => env('APP_TIMEZONE', 'UTC'),

    'locale' => env('APP_LOCALE', 'pt'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'key' => env('APP_KEY'),

    /* Token Bearer da API /api/leads. Vazio = API desactivada (503). */
    'api_token' => env('API_TOKEN', ''),

    /* TrimStrings: '' vira null no input. */
    'convert_empty_strings_to_null' => true,

    /* Cabeçalhos de segurança (SecurityHeaders). Um valor vazio desactiva o cabeçalho. */
    'security_headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ],

    /* SEO e partilha social (meta description, Open Graph). */
    'seo' => [
        'title' => env('SEO_TITLE', 'Slendie — a tua landing page no ar em minutos'),
        'description' => env('SEO_DESCRIPTION', 'Landing page leve, rápida e sem Node. Recolhe contactos com validação e protecção anti-spam.'),
        'image' => env('SEO_IMAGE', ''),
    ],

    /* Providers da aplicação (os da framework são registados automaticamente). */
    'providers' => [],
];
