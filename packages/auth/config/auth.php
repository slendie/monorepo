<?php

declare(strict_types=1);

return [
    /*
    | Guard por omissão: "web" (sessão) para o painel. O "api" (token Bearer) usa-se com ->middleware('auth:api').
    */
    'defaults' => [
        'guard' => 'web',
    ],

    'guards' => [
        'web' => ['driver' => 'session', 'provider' => 'users'],
        'api' => ['driver' => 'token', 'provider' => 'users'],
    ],

    /*
    | Onde estão os utilizadores. Sem App\Models\User publicado, usa-se o model do package.
    */
    'providers' => [
        'users' => [
            'driver' => 'model',
            'model' => env('AUTH_MODEL', 'App\\Models\\User'),
        ],
    ],

    /* Para onde ir depois de entrar (e para os visitantes que já estão autenticados). */
    'home' => '/admin',

    /* Cookie "lembrar-me": nome e dias de validade. */
    'remember' => [
        'cookie' => 'slendie_remember',
        'days' => 400,
    ],

    /* Falhas de login permitidas por email+IP e duração da janela, em segundos. */
    'throttle' => [
        'max_attempts' => 5,
        'decay_seconds' => 60,
    ],

    /* Hash das passwords: "bcrypt" (por omissão) ou "argon2id". */
    'hashing' => [
        'driver' => env('HASH_DRIVER', 'bcrypt'),
        'bcrypt' => ['rounds' => (int) env('BCRYPT_ROUNDS', 12)],
        'argon2id' => ['memory' => 65536, 'time' => 4, 'threads' => 1],
    ],
];
