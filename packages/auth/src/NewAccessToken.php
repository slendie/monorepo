<?php

declare(strict_types=1);

namespace Slendie\Auth;

use Slendie\Auth\Models\PersonalAccessToken;

/**
 * Resultado de `createToken()`: o token guardado e o texto em claro, que só existe neste momento.
 */
final readonly class NewAccessToken
{
    public function __construct(
        public PersonalAccessToken $accessToken,
        public string $plainTextToken,
    ) {
    }

    public function __toString(): string
    {
        return $this->plainTextToken;
    }
}
