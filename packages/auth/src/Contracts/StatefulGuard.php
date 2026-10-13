<?php

declare(strict_types=1);

namespace Slendie\Auth\Contracts;

/**
 * Guard que mantém o utilizador entre pedidos (sessão + remember-me).
 */
interface StatefulGuard extends Guard
{
    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attempt(array $credentials = [], bool $remember = false): bool;

    /**
     * Autentica só para o pedido actual (sem sessão nem cookies).
     *
     * @param  array<string, mixed>  $credentials
     */
    public function once(array $credentials = []): bool;

    public function login(Authenticatable $user, bool $remember = false): void;

    public function loginUsingId(mixed $id, bool $remember = false): ?Authenticatable;

    public function logout(): void;

    /** O utilizador actual veio do cookie remember-me? */
    public function viaRemember(): bool;
}
