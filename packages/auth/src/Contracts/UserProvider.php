<?php

declare(strict_types=1);

namespace Slendie\Auth\Contracts;

interface UserProvider
{
    public function retrieveById(mixed $identifier): ?Authenticatable;

    /** Utilizador cujo remember token (em claro) corresponde ao guardado. */
    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable;

    /** Guarda o remember token (em claro; o provider decide como o persiste). */
    public function updateRememberToken(Authenticatable $user, string $token): void;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool;

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials): void;
}
