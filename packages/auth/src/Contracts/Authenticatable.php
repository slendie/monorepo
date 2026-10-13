<?php

declare(strict_types=1);

namespace Slendie\Auth\Contracts;

/**
 * Um utilizador que pode autenticar-se. O trait `Slendie\Auth\Authenticatable` implementa-o num model.
 */
interface Authenticatable
{
    public function getAuthIdentifierName(): string;

    public function getAuthIdentifier(): mixed;

    public function getAuthPassword(): ?string;

    public function getRememberToken(): ?string;

    public function setRememberToken(?string $value): void;

    public function getRememberTokenName(): string;
}
