<?php

declare(strict_types=1);

namespace Slendie\Auth\Middleware;

use Closure;
use Slendie\Auth\AuthManager;
use Slendie\Exceptions\UrlGenerationException;
use Slendie\Http\HttpMethod;
use Slendie\Http\Request;
use Slendie\Http\Response;
use Slendie\Routing\Redirector;
use Slendie\Routing\UrlGenerator;

/**
 * Alias `auth` (ou `auth:api`, `auth:web,api`): deixa passar quem está autenticado num dos guards.
 * Visitantes: JSON → 401; web → redirecciona para a rota `login` guardando o URL pretendido (`url.intended`).
 */
class Authenticate
{
    public function __construct(
        protected AuthManager $auth,
        protected Redirector $redirector,
        protected UrlGenerator $url,
    ) {
    }

    public function handle(Request $request, Closure $next, string ...$guards): mixed
    {
        foreach ($guards === [] ? [null] : $guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $this->auth->shouldUse($guard ?? $this->auth->getDefaultDriver());

                return $next($request);
            }
        }

        return $this->unauthenticated($request);
    }

    protected function unauthenticated(Request $request): Response
    {
        // Sem sessão não há página de login para onde ir (pedidos da API): 401.
        if ($request->expectsJson() || ! $request->hasSession()) {
            return response()->json(['message' => 'Unauthenticated.'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        if ($request->method() === HttpMethod::GET) {
            $request->session()->put('url.intended', $request->fullUrl());
        }

        return $this->redirector->to($this->loginUrl());
    }

    protected function loginUrl(): string
    {
        try {
            return $this->url->route('login');
        } catch (UrlGenerationException) {
            return $this->url->to('/login');
        }
    }
}
