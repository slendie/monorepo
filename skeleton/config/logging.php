<?php

declare(strict_types=1);

return [
    /*
    | Canal por omissão: uma das chaves de "channels".
    */
    'default' => env('LOG_CHANNEL', 'daily'),

    /*
    | Drivers: single, daily, stderr, null, stack (lista de canais).
    | Novos drivers: Log::extend('slack', fn (array $config) => ...) num service provider.
    */
    'channels' => [
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/slendie.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/slendie.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],

        'stderr' => [
            'driver' => 'stderr',
            'level' => env('LOG_LEVEL', 'debug'),
        ],

        'null' => [
            'driver' => 'null',
        ],

        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'stderr'],
        ],
    ],
];
