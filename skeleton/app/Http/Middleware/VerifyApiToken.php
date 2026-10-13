<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Slendie\Http\Request;

/**
 * Protege a API com um token Bearer fixo (`API_TOKEN` no .env). Sem base de dados; chega para o painel React
 * até o package `slendie/auth` trazer guards por token.
 */
class VerifyApiToken
{
    public function handle(Request $request, Closure $next): mixed
    {
        $expected = (string) config('app.api_token', '');

        if ($expected === '') {
            return response()->json(['message' => 'API desactivada.'], 503);
        }

        $given = $request->bearerToken();

        if ($given === null || ! hash_equals($expected, $given)) {
            return response()->json(['message' => 'Não autenticado.'], 401, ['WWW-Authenticate' => 'Bearer']);
        }

        return $next($request);
    }
}
