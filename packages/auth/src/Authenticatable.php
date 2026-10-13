<?php

declare(strict_types=1);

namespace Slendie\Auth;

use Slendie\Auth\Hashing\Hasher;

/**
 * Para o model `User`: implementa `Slendie\Auth\Contracts\Authenticatable` sobre as colunas `id`, `password` e `remember_token`.
 * Também faz hash automático do `password` ao atribuí-lo (um hash já feito não é refeito).
 */
trait Authenticatable
{
    public function getAuthIdentifierName(): string
    {
        return $this->getKeyName();
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->getAttribute($this->getAuthIdentifierName());
    }

    public function getAuthPassword(): ?string
    {
        $password = $this->getAttribute('password');

        return is_string($password) ? $password : null;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function getRememberToken(): ?string
    {
        $token = $this->getAttribute($this->getRememberTokenName());

        return is_string($token) && $token !== '' ? $token : null;
    }

    public function setRememberToken(?string $value): void
    {
        $this->attributes[$this->getRememberTokenName()] = $value;
    }

    public function setPasswordAttribute(mixed $value): void
    {
        if (! is_string($value) || $value === '') {
            $this->attributes['password'] = $value;

            return;
        }

        $hasher = app(Hasher::class);

        $this->attributes['password'] = $hasher instanceof Hasher && ! $hasher->isHashed($value)
            ? $hasher->make($value)
            : $value;
    }
}
