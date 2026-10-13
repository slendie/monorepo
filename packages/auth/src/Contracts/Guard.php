<?php

declare(strict_types=1);

namespace Slendie\Auth\Contracts;

interface Guard
{
    public function check(): bool;

    public function guest(): bool;

    public function user(): ?Authenticatable;

    public function id(): int|string|null;

    /**
     * Valida credenciais sem iniciar sessão.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool;

    /** Define o utilizador autenticado (usado por `actingAs()` nos testes). */
    public function setUser(Authenticatable $user): static;
}
