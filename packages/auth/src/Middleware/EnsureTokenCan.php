<?php

declare(strict_types=1);

namespace Slendie\Auth\Middleware;

use Closure;
use Slendie\Auth\AuthManager;
use Slendie\Http\Request;

/**
 * Alias `ability:leads.read,leads.write`: o token com que o pedido se autenticou tem de ter todas as abilities.
 * Utilizadores autenticados por sessão (sem token) passam: têm acesso total, como o próprio painel.
 */
class EnsureTokenCan
{
    public function __construct(protected AuthManager $auth)
    {
    }

    public function handle(Request $request, Closure $next, string ...$abilities): mixed
    {
        $user = $this->auth->guard()->user();

        if ($user === null) {
            return $this->deny($request, 401, 'Unauthenticated.');
        }

        $token = method_exists($user, 'currentAccessToken') ? $user->currentAccessToken() : null;

        if ($token !== null) {
            foreach ($abilities as $ability) {
                if (! $token->can($ability)) {
                    return $this->deny($request, 403, 'Invalid ability provided.');
                }
            }
        }

        return $next($request);
    }

    protected function deny(Request $request, int $status, string $message): mixed
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => $message], $status);
        }

        abort($status, $message);
    }
}
