<?php

declare(strict_types=1);

return [
    /* Pastas onde procurar views (por omissão, resources/views). */
    'paths' => [
        dirname(__DIR__).'/resources/views',
    ],

    /* Onde guardar as views compiladas. */
    'compiled' => dirname(__DIR__).'/storage/framework/views',
];
