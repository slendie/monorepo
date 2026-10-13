<?php

declare(strict_types=1);

// O custo mínimo do bcrypt mantém os testes rápidos.
return [
    'hashing' => [
        'driver' => 'bcrypt',
        'bcrypt' => ['rounds' => 4],
        'argon2id' => ['memory' => 1024, 'time' => 1, 'threads' => 1],
    ],
];
