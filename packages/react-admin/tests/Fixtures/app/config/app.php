<?php

declare(strict_types=1);

return [
    'name' => 'AdminFixture',
    'env' => env('APP_ENV', 'production'),
    'debug' => true,
    'url' => 'http://localhost',
    'timezone' => 'UTC',
    'locale' => 'pt',
    'key' => 'base64:dGVzdGluZy1rZXktdGVzdGluZy1rZXktMTIzNDU2Nzg=',
    'providers' => [
        Slendie\Auth\AuthServiceProvider::class,
        Slendie\ReactAdmin\ReactAdminServiceProvider::class,
    ],
];
