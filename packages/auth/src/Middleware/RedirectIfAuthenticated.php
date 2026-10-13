<?php

declare(strict_types=1);

namespace Slendie\Auth\Middleware;

use Closure;
use Slendie\Auth\AuthManager;
use Slendie\Http\Request;
use Slendie\Routing\Redirector;

/**
 * Alias `guest`: quem já está autenticado vai para `config('auth.home', '/admin')`.
 */
class RedirectIfAuthenticated
{
    public function __construct(protected AuthManager $auth, protected Redirector $redirector)
    {
    }

    public function handle(Request $request, Closure $next, string ...$guards): mixed
    {
        foreach ($guards === [] ? [null] : $guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                $home = config('auth.home', '/admin');

                return $this->redirector->to(is_string($home) && $home !== '' ? $home : '/admin');
            }
        }

        return $next($request);
    }
}
