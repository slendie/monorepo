<?php

declare(strict_types=1);

namespace Slendie\Auth;

use Closure;
use InvalidArgumentException;
use LogicException;
use Slendie\Auth\Contracts\Authenticatable;
use Slendie\Auth\Contracts\Guard;
use Slendie\Auth\Contracts\StatefulGuard;
use Slendie\Auth\Contracts\UserProvider;
use Slendie\Auth\Guards\SessionGuard;
use Slendie\Auth\Guards\TokenGuard;
use Slendie\Auth\Hashing\Hasher;
use Slendie\Auth\Providers\ModelUserProvider;
use Slendie\Foundation\Application;
use Slendie\Http\Cookie;
use Slendie\Http\Request;
use Slendie\Support\Config;

/**
 * Serviço `auth`: devolve os guards definidos em `config/auth.php` e delega no guard por omissão
 * (`auth()->check()`, `auth()->attempt(...)`, `auth('api')->user()`).
 */
final class AuthManager
{
    /** @var array<string, Guard> */
    private array $guards = [];

    /** @var array<string, UserProvider> */
    private array $providers = [];

    /** @var array<string, Closure(Application, string, array<string, mixed>, UserProvider): Guard> */
    private array $customCreators = [];

    private ?string $defaultGuard = null;

    public function __construct(
        private readonly Application $app,
        private readonly Config $config,
        private readonly Hasher $hasher,
    ) {
    }

    // -------------------------------------------------------------------------
    // Guards
    // -------------------------------------------------------------------------

    public function guard(?string $name = null): Guard
    {
        $name ??= $this->getDefaultDriver();

        return $this->guards[$name] ??= $this->resolve($name);
    }

    public function getDefaultDriver(): string
    {
        if ($this->defaultGuard !== null) {
            return $this->defaultGuard;
        }

        $default = $this->config->get('auth.defaults.guard', 'web');

        return is_string($default) && $default !== '' ? $default : 'web';
    }

    /** Guard por omissão do pedido actual (o middleware `auth:api` usa-o para que `$request->user()` seja o do `api`). */
    public function shouldUse(string $name): void
    {
        $this->defaultGuard = $name;
    }

    public function resetDefaultGuard(): void
    {
        $this->defaultGuard = null;
    }

    /**
     * Regista um driver de guard: `fn (Application $app, string $name, array $config, UserProvider $provider): Guard`.
     *
     * @param  Closure(Application, string, array<string, mixed>, UserProvider): Guard  $callback
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    public function forgetGuards(): static
    {
        $this->guards = [];

        return $this;
    }

    public function provider(string $name = 'users'): UserProvider
    {
        return $this->providers[$name] ??= $this->createProvider($name);
    }

    /**
     * Cookies que os guards de sessão deixaram em fila (remember-me).
     *
     * @return list<Cookie>
     */
    public function pullQueuedCookies(): array
    {
        $cookies = [];

        foreach ($this->guards as $guard) {
            if ($guard instanceof SessionGuard) {
                $cookies = [...$cookies, ...$guard->pullQueuedCookies()];
            }
        }

        return $cookies;
    }

    // -------------------------------------------------------------------------
    // Atalhos para o guard por omissão
    // -------------------------------------------------------------------------

    public function user(): ?Authenticatable
    {
        return $this->guard()->user();
    }

    public function check(): bool
    {
        return $this->guard()->check();
    }

    public function guest(): bool
    {
        return $this->guard()->guest();
    }

    public function id(): int|string|null
    {
        return $this->guard()->id();
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        return $this->stateful()->attempt($credentials, $remember);
    }

    /**
     * @param  array<string, mixed>  $credentials
     */
    public function once(array $credentials = []): bool
    {
        return $this->stateful()->once($credentials);
    }

    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->stateful()->login($user, $remember);
    }

    public function loginUsingId(mixed $id, bool $remember = false): ?Authenticatable
    {
        return $this->stateful()->loginUsingId($id, $remember);
    }

    public function logout(): void
    {
        $this->stateful()->logout();
    }

    public function viaRemember(): bool
    {
        return $this->stateful()->viaRemember();
    }

    // -------------------------------------------------------------------------

    private function stateful(): StatefulGuard
    {
        $guard = $this->guard();

        if (! $guard instanceof StatefulGuard) {
            throw new LogicException("O guard [{$this->getDefaultDriver()}] não mantém estado (sessão): use auth('web').");
        }

        return $guard;
    }

    private function resolve(string $name): Guard
    {
        $config = $this->config->get('auth.guards.'.$name);

        if (! is_array($config)) {
            throw new InvalidArgumentException("O guard de autenticação [{$name}] não está definido em config/auth.php.");
        }

        /** @var array<string, mixed> $config */
        $driver = is_string($config['driver'] ?? null) ? $config['driver'] : '';
        $provider = $this->provider(is_string($config['provider'] ?? null) ? $config['provider'] : 'users');

        if (isset($this->customCreators[$driver])) {
            return ($this->customCreators[$driver])($this->app, $name, $config, $provider);
        }

        $request = fn (): ?Request => $this->request();

        return match ($driver) {
            'session' => new SessionGuard($name, $provider, $request, $this->config),
            'token' => new TokenGuard($name, $provider, $request),
            default => throw new InvalidArgumentException("O driver de guard [{$driver}] (guard [{$name}]) não é suportado."),
        };
    }

    private function createProvider(string $name): UserProvider
    {
        $config = $this->config->get('auth.providers.'.$name);

        if (! is_array($config)) {
            throw new InvalidArgumentException("O provider de utilizadores [{$name}] não está definido em config/auth.php.");
        }

        $driver = $config['driver'] ?? 'model';

        if ($driver !== 'model') {
            throw new InvalidArgumentException('O driver de provider ['.(is_scalar($driver) ? (string) $driver : gettype($driver)).'] não é suportado (apenas model).');
        }

        $model = $config['model'] ?? \Slendie\Auth\Models\User::class;

        return new ModelUserProvider($this->hasher, is_string($model) ? $model : \Slendie\Auth\Models\User::class);
    }

    private function request(): ?Request
    {
        if (! $this->app->bound('request')) {
            return null;
        }

        $request = $this->app->make('request');

        return $request instanceof Request ? $request : null;
    }
}
