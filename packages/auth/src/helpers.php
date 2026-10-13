<?php

declare(strict_types=1);

use Slendie\Auth\AuthManager;
use Slendie\Auth\Contracts\Guard;
use Slendie\Auth\Hashing\Hasher;

if (! function_exists('auth')) {
    /**
     * Sem argumentos devolve o AuthManager (`auth()->check()`); com o nome de um guard, esse guard (`auth('api')->user()`).
     */
    function auth(?string $guard = null): AuthManager|Guard
    {
        $manager = app('auth');

        if (! $manager instanceof AuthManager) {
            throw new LogicException('O serviço [auth] não está registado: instale slendie/auth.');
        }

        return $guard === null ? $manager : $manager->guard($guard);
    }
}

if (! function_exists('bcrypt')) {
    /**
     * Hash de uma password com o algoritmo configurado em `auth.hashing`.
     *
     * @param  array<string, mixed>  $options
     */
    function bcrypt(string $value, array $options = []): string
    {
        $hasher = app(Hasher::class);

        if (! $hasher instanceof Hasher) {
            throw new LogicException('O serviço [hash] não está registado: instale slendie/auth.');
        }

        return $hasher->make($value, $options);
    }
}
