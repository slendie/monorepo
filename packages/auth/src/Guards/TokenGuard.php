<?php

declare(strict_types=1);

namespace Slendie\Auth\Guards;

use Closure;
use Slendie\Auth\Contracts\Authenticatable;
use Slendie\Auth\Contracts\Guard;
use Slendie\Auth\Contracts\UserProvider;
use Slendie\Auth\Models\PersonalAccessToken;
use Slendie\Http\Request;

/**
 * Autenticação por `Authorization: Bearer {id}|{segredo}`: procura o sha256 do segredo em `personal_access_tokens`,
 * recusa tokens expirados e regista `last_used_at` (no máximo uma vez por minuto).
 */
final class TokenGuard implements Guard
{
    use BindsToRequest;

    private ?Authenticatable $user = null;

    /**
     * @param  Closure(): ?Request  $requestResolver
     */
    public function __construct(
        private readonly string $name,
        private readonly UserProvider $provider,
        Closure $requestResolver,
    ) {
        $this->requestResolver = $requestResolver;
    }

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function id(): int|string|null
    {
        $id = $this->user()?->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }

    public function user(): ?Authenticatable
    {
        $request = $this->syncRequest();

        if ($this->user !== null || $request === null) {
            return $this->user;
        }

        $plain = $request->bearerToken();

        if ($plain === null || $plain === '') {
            return null;
        }

        return $this->user = $this->userFromToken($plain);
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->pinned = true;
        $this->syncRequest();

        return $this;
    }

    /**
     * @param  array<string, mixed>  $credentials  `['token' => '{id}|{segredo}']`
     */
    public function validate(array $credentials = []): bool
    {
        $plain = $credentials['token'] ?? null;

        return is_string($plain) && $plain !== '' && $this->userFromToken($plain, touch: false) !== null;
    }

    public function name(): string
    {
        return $this->name;
    }

    private function resetRequestState(): void
    {
        $this->user = null;
    }

    private function userFromToken(string $plain, bool $touch = true): ?Authenticatable
    {
        $token = PersonalAccessToken::findToken($plain);

        if ($token === null || $token->isExpired()) {
            return null;
        }

        $user = $this->provider->retrieveById($token->user_id);

        if ($user === null) {
            return null;
        }

        if (method_exists($user, 'withAccessToken')) {
            $user->withAccessToken($token);
        }

        if ($touch) {
            $token->touchLastUsed();
        }

        return $user;
    }
}
