<?php

declare(strict_types=1);

namespace Slendie\Auth\Guards;

use Closure;
use RuntimeException;
use Slendie\Auth\Contracts\Authenticatable;
use Slendie\Auth\Contracts\StatefulGuard;
use Slendie\Auth\Contracts\UserProvider;
use Slendie\Http\Cookie;
use Slendie\Http\Request;
use Slendie\Support\Config;
use Slendie\Support\Str;

/**
 * Autenticação por sessão com remember-me.
 *
 * O remember-me é uma cookie `id|token|hmac` (HttpOnly, SameSite=Lax, 400 dias por omissão): o `token` em claro só
 * existe na cookie, a base de dados guarda o seu sha256 e o `hmac` (sha256 com a APP_KEY) impede forjar cookies.
 * Como o guard não tem a resposta, as cookies ficam em fila e o middleware `AuthenticationContext` junta-as.
 */
final class SessionGuard implements StatefulGuard
{
    use BindsToRequest;

    private ?Authenticatable $user = null;

    private bool $loggedOut = false;

    private bool $viaRemember = false;

    /** @var list<Cookie> */
    private array $queuedCookies = [];

    /**
     * @param  Closure(): ?Request  $requestResolver
     */
    public function __construct(
        private readonly string $name,
        private readonly UserProvider $provider,
        Closure $requestResolver,
        private readonly Config $config,
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

        if ($this->loggedOut) {
            return null;
        }

        if ($this->user !== null || $request === null) {
            return $this->user;
        }

        if ($request->hasSession()) {
            $id = $request->session()->get($this->sessionKey());

            if ($id !== null) {
                $this->user = $this->provider->retrieveById($id);
            }
        }

        if ($this->user === null) {
            $this->user = $this->userFromRememberCookie($request);
        }

        return $this->user;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->loggedOut = false;
        $this->pinned = true;
        $this->syncRequest();

        return $this;
    }

    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        return $user !== null && $this->provider->validateCredentials($user, $credentials);
    }

    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null || ! $this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->provider->rehashPasswordIfRequired($user, $credentials);
        $this->login($user, $remember);

        return true;
    }

    public function once(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($user === null || ! $this->provider->validateCredentials($user, $credentials)) {
            return false;
        }

        $this->syncRequest();
        $this->user = $user;
        $this->loggedOut = false;

        return true;
    }

    public function login(Authenticatable $user, bool $remember = false): void
    {
        $request = $this->syncRequest();

        if ($request !== null && $request->hasSession()) {
            $session = $request->session();
            $session->put($this->sessionKey(), $user->getAuthIdentifier());
            $session->regenerate();
            $session->regenerateToken();
            $this->pinned = false;
        } else {
            // Sem sessão (consola, testes sem pedido) o login vale até ao logout.
            $this->pinned = true;
        }

        if ($remember && $request !== null) {
            $token = Str::random(60);
            $this->provider->updateRememberToken($user, $token);
            $this->queuedCookies[] = $this->rememberCookie($request, $user, $token);
        }

        $this->user = $user;
        $this->loggedOut = false;
        $this->viaRemember = false;
    }

    public function loginUsingId(mixed $id, bool $remember = false): ?Authenticatable
    {
        $user = $this->provider->retrieveById($id);

        if ($user !== null) {
            $this->login($user, $remember);
        }

        return $user;
    }

    public function logout(): void
    {
        $user = $this->user();
        $request = $this->request();

        if ($request !== null && $request->hasSession()) {
            $request->session()->forget($this->sessionKey());
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        if ($user !== null && $user->getRememberToken() !== null) {
            // O cookie remember-me deixa de servir em qualquer dispositivo.
            $this->provider->updateRememberToken($user, Str::random(60));
        }

        if ($request !== null) {
            $this->queuedCookies[] = Cookie::forget($this->rememberCookieName(), $this->path(), $this->domain());
        }

        $this->user = null;
        $this->loggedOut = true;
        $this->viaRemember = false;
        $this->pinned = false;
    }

    public function viaRemember(): bool
    {
        $this->user();

        return $this->viaRemember;
    }

    /**
     * Cookies a enviar na resposta do pedido actual (o middleware `AuthenticationContext` chama-o).
     *
     * @return list<Cookie>
     */
    public function pullQueuedCookies(): array
    {
        $cookies = $this->queuedCookies;
        $this->queuedCookies = [];

        return $cookies;
    }

    public function rememberCookieName(): string
    {
        $name = $this->config->get('auth.remember.cookie', 'slendie_remember');

        return is_string($name) && $name !== '' ? $name : 'slendie_remember';
    }

    private function resetRequestState(): void
    {
        $this->user = null;
        $this->loggedOut = false;
        $this->viaRemember = false;
    }

    private function sessionKey(): string
    {
        return 'login_'.$this->name.'_'.sha1(self::class);
    }

    private function userFromRememberCookie(Request $request): ?Authenticatable
    {
        $value = $request->cookie($this->rememberCookieName());

        if (! is_string($value) || $value === '') {
            return null;
        }

        $parts = explode('|', $value);

        if (count($parts) !== 3 || ! hash_equals($this->signature($parts[0].'|'.$parts[1]), $parts[2])) {
            return null;
        }

        $user = $this->provider->retrieveByToken($parts[0], $parts[1]);

        if ($user === null) {
            return null;
        }

        $this->viaRemember = true;

        if ($request->hasSession()) {
            $request->session()->put($this->sessionKey(), $user->getAuthIdentifier());
            $request->session()->regenerate();
        }

        return $user;
    }

    private function rememberCookie(Request $request, Authenticatable $user, string $token): Cookie
    {
        $payload = $user->getAuthIdentifier().'|'.$token;
        $days = $this->config->get('auth.remember.days', 400);
        $secure = $this->config->get('session.secure');

        return new Cookie(
            $this->rememberCookieName(),
            $payload.'|'.$this->signature($payload),
            time() + (is_numeric($days) ? (int) $days : 400) * 86400,
            $this->path(),
            $this->domain(),
            $secure === null ? $request->isSecure() : (bool) $secure,
            true,
            'lax',
        );
    }

    private function signature(string $payload): string
    {
        return hash_hmac('sha256', $payload, $this->key());
    }

    private function key(): string
    {
        $key = $this->config->get('app.key');

        if (! is_string($key) || $key === '') {
            throw new RuntimeException('APP_KEY em falta: corra "php slendie key:generate" para usar o remember-me.');
        }

        if (str_starts_with($key, 'base64:')) {
            $decoded = base64_decode(substr($key, 7), true);

            return $decoded !== false ? $decoded : $key;
        }

        return $key;
    }

    private function path(): string
    {
        $path = $this->config->get('session.path', '/');

        return is_string($path) && $path !== '' ? $path : '/';
    }

    private function domain(): ?string
    {
        $domain = $this->config->get('session.domain');

        return is_string($domain) && $domain !== '' ? $domain : null;
    }
}
