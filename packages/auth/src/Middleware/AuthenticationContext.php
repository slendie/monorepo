<?php

declare(strict_types=1);

namespace Slendie\Auth\Middleware;

use Closure;
use Slendie\Auth\AuthManager;
use Slendie\Http\Request;
use Slendie\Http\Response;

/**
 * Middleware global do package: liga `$request->user()` ao guard activo e junta à resposta as cookies que os guards
 * deixaram em fila (remember-me). Regista-o o `AuthServiceProvider`; não é preciso usá-lo à mão.
 */
class AuthenticationContext
{
    public function __construct(protected AuthManager $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $this->auth->resetDefaultGuard();
        $request->setUserResolver(fn (): mixed => $this->auth->guard()->user());

        /** @var Response $response */
        $response = $next($request);

        foreach ($this->auth->pullQueuedCookies() as $cookie) {
            $response->cookie($cookie);
        }

        return $response;
    }
}
