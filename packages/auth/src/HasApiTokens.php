<?php

declare(strict_types=1);

namespace Slendie\Auth;

use DateTimeInterface;
use Slendie\Auth\Models\PersonalAccessToken;
use Slendie\Model\Relations\HasMany;
use Slendie\Support\Str;

/**
 * Para o model `User` que usa tokens de API: `$user->createToken('cli', ['leads.read'])->plainTextToken`.
 */
trait HasApiTokens
{
    protected ?PersonalAccessToken $accessToken = null;

    /**
     * Cria um token. O texto em claro (`{id}|{segredo}`) devolve-se uma única vez; na base de dados fica o sha256 do segredo.
     *
     * @param  list<string>  $abilities  `['*']` = todas
     */
    public function createToken(string $name, array $abilities = ['*'], ?DateTimeInterface $expiresAt = null): NewAccessToken
    {
        $secret = Str::random(40);

        $token = PersonalAccessToken::create([
            'user_id' => $this->getKey(),
            'name' => $name,
            'token' => PersonalAccessToken::hash($secret),
            'abilities' => $abilities,
            'expires_at' => $expiresAt?->format('Y-m-d H:i:s'),
        ]);

        return new NewAccessToken($token, $token->getKey().'|'.$secret);
    }

    public function tokens(): HasMany
    {
        return $this->hasMany(PersonalAccessToken::class, 'user_id', $this->getKeyName());
    }

    /** O token com que este utilizador se autenticou (null se veio da sessão). */
    public function currentAccessToken(): ?PersonalAccessToken
    {
        return $this->accessToken;
    }

    public function withAccessToken(PersonalAccessToken $token): static
    {
        $this->accessToken = $token;

        return $this;
    }

    /** O token actual tem esta ability? Sem token actual, não. */
    public function tokenCan(string $ability): bool
    {
        return $this->accessToken?->can($ability) ?? false;
    }
}
