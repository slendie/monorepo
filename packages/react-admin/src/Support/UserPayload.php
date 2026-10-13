<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Support;

use Slendie\Model\Model;

/** Dados públicos do utilizador autenticado, tal como a SPA os recebe. */
final class UserPayload
{
    /** @return array{id: mixed, name: mixed, email: mixed} */
    public static function from(mixed $user): array
    {
        if (! $user instanceof Model) {
            return ['id' => null, 'name' => null, 'email' => null];
        }

        return [
            'id' => $user->getKey(),
            'name' => $user->getAttribute('name'),
            'email' => $user->getAttribute('email'),
        ];
    }
}
