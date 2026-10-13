<?php

declare(strict_types=1);

namespace Slendie\Auth\Models;

use DateTimeImmutable;
use Slendie\Model\Model;

/**
 * Token de API. Na base de dados fica só o `sha256` do token; o texto em claro mostra-se uma vez, ao criá-lo.
 *
 * @property int $id
 * @property int|string $user_id
 * @property string $name
 * @property string $token
 * @property list<string> $abilities
 * @property DateTimeImmutable|null $last_used_at
 * @property DateTimeImmutable|null $expires_at
 */
class PersonalAccessToken extends Model
{
    /** Intervalo mínimo, em segundos, entre duas actualizações de `last_used_at`. */
    public const int LAST_USED_THROTTLE = 60;

    protected ?string $table = 'personal_access_tokens';

    /** @var list<string> */
    protected array $fillable = ['user_id', 'name', 'token', 'abilities', 'expires_at'];

    /** @var list<string> */
    protected array $hidden = ['token'];

    /** @var array<string, string> */
    protected array $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public static function hash(string $plainText): string
    {
        return hash('sha256', $plainText);
    }

    /**
     * Procura um token pelo texto em claro (`{id}|{segredo}` ou só `{segredo}`).
     */
    public static function findToken(string $plainText): ?self
    {
        $secret = str_contains($plainText, '|') ? substr($plainText, strpos($plainText, '|') + 1) : $plainText;

        if ($secret === '') {
            return null;
        }

        $token = static::query()->where('token', self::hash($secret))->first();

        return $token instanceof self ? $token : null;
    }

    public function can(string $ability): bool
    {
        $abilities = $this->abilities;

        return in_array('*', $abilities, true) || in_array($ability, $abilities, true);
    }

    public function cant(string $ability): bool
    {
        return ! $this->can($ability);
    }

    public function isExpired(): bool
    {
        $expires = $this->expires_at;

        return $expires instanceof DateTimeImmutable && $expires->getTimestamp() <= time();
    }

    /**
     * Regista o uso do token, no máximo uma vez por minuto (sem mexer em `updated_at`).
     */
    public function touchLastUsed(): void
    {
        $last = $this->last_used_at;

        if ($last instanceof DateTimeImmutable && time() - $last->getTimestamp() < self::LAST_USED_THROTTLE) {
            return;
        }

        $now = $this->freshTimestampString();

        $this->newQuery()->getQuery()->where($this->getKeyName(), $this->getKey())->update(['last_used_at' => $now]);
        $this->setRawAttributes([...$this->getAttributes(), 'last_used_at' => $now], true);
    }
}
