<?php

declare(strict_types=1);

namespace Slendie\Auth\Providers;

use InvalidArgumentException;
use Slendie\Auth\Contracts\Authenticatable;
use Slendie\Auth\Contracts\UserProvider;
use Slendie\Auth\Hashing\Hasher;
use Slendie\Auth\Models\User;
use Slendie\Model\Model;

/**
 * Procura utilizadores através de um model Slendie (`config('auth.providers.users.model')`).
 *
 * O remember token guarda-se na coluna `remember_token` como sha256; o cookie leva o valor em claro.
 */
final class ModelUserProvider implements UserProvider
{
    /** @var class-string<Model&Authenticatable> */
    private readonly string $model;

    public function __construct(private readonly Hasher $hasher, string $model)
    {
        // Sem `App\Models\User` publicado, usa-se o model do package.
        $model = class_exists($model) ? $model : User::class;

        if (! is_subclass_of($model, Model::class) || ! is_subclass_of($model, Authenticatable::class)) {
            throw new InvalidArgumentException("O model [{$model}] tem de estender Slendie\\Model\\Model e implementar Slendie\\Auth\\Contracts\\Authenticatable.");
        }

        $this->model = $model;
    }

    /** @return class-string<Model&Authenticatable> */
    public function model(): string
    {
        return $this->model;
    }

    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        if (! is_int($identifier) && ! is_string($identifier)) {
            return null;
        }

        $model = new ($this->model)();
        $user = $model->newQuery()->where($model->getAuthIdentifierName(), $identifier)->first();

        return $user instanceof Authenticatable ? $user : null;
    }

    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable
    {
        $user = $this->retrieveById($identifier);
        $stored = $user?->getRememberToken();

        if ($user === null || $stored === null || $token === '') {
            return null;
        }

        return hash_equals($stored, hash('sha256', $token)) ? $user : null;
    }

    public function updateRememberToken(Authenticatable $user, string $token): void
    {
        $hashed = hash('sha256', $token);
        $user->setRememberToken($hashed);

        if ($user instanceof Model) {
            // Directo no query builder: não mexe em `updated_at` nem dispara eventos.
            $user->newQuery()->getQuery()
                ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
                ->update([$user->getRememberTokenName() => $hashed]);
        }
    }

    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $credentials = array_filter(
            $credentials,
            static fn (string $key): bool => $key !== 'password',
            ARRAY_FILTER_USE_KEY,
        );

        if ($credentials === []) {
            return null;
        }

        $query = (new ($this->model)())->newQuery();

        foreach ($credentials as $key => $value) {
            if (is_array($value)) {
                $query->whereIn($key, $value);
            } else {
                $query->where($key, $value);
            }
        }

        $user = $query->first();

        return $user instanceof Authenticatable ? $user : null;
    }

    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $password = $credentials['password'] ?? null;

        return is_string($password) && $this->hasher->check($password, $user->getAuthPassword());
    }

    public function rehashPasswordIfRequired(Authenticatable $user, array $credentials): void
    {
        $password = $credentials['password'] ?? null;
        $current = $user->getAuthPassword();

        if (! is_string($password) || $current === null || ! $this->hasher->needsRehash($current)) {
            return;
        }

        $hash = $this->hasher->make($password);

        if ($user instanceof Model) {
            $user->forceFill(['password' => $hash]);
            $user->newQuery()->getQuery()
                ->where($user->getAuthIdentifierName(), $user->getAuthIdentifier())
                ->update(['password' => $hash]);
            $user->syncOriginal();
        }
    }
}
