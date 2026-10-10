# Middleware

```bash
php slendie make:middleware EnsureIsAdmin     # comando da peça de consola; até lá, cria a classe à mão
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Slendie\Http\Request;
use Slendie\Http\Response;

final class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('is_admin')) {
            return redirect()->route('login');
        }

        $request->setAttribute('admin', true);          // passar dados para o controller

        $response = $next($request);                    // continua o pipeline

        return $response->header('X-Admin', '1');       // alterar a resposta à saída
    }
}
```

Um middleware pode receber **parâmetros** (`'role:admin,editor'`), passados como strings depois de `$next`:

```php
public function handle(Request $request, Closure $next, string ...$roles): Response
```

E pode ter `terminate(Request $request, Response $response): void`, chamado por `Application::terminate()` depois de a
resposta ser enviada (é o que `public/index.php` faz). Os middlewares são resolvidos pelo container, por isso o
construtor aceita dependências. Também é possível usar uma `Closure` como pipe.

## Registo

Em `bootstrap/app.php`:

```php
->withMiddleware(function (Kernel $kernel) {
    $kernel->pushMiddleware(MyGlobalMiddleware::class);                   // global, no fim
    $kernel->prependMiddleware(First::class);                             // global, no início
    $kernel->appendMiddlewareToGroup('web', MyWebMiddleware::class);      // ou prependMiddlewareToGroup()
    $kernel->aliasMiddleware('admin', EnsureIsAdmin::class);              // Route::...->middleware('admin')
})
```

Ou num Service Provider (é o que os packages fazem), via `Router`:

```php
public function boot(Router $router): void
{
    $router->aliasMiddleware('admin', EnsureIsAdmin::class);
    $router->pushMiddlewareToGroup('web', X::class);       // prependMiddlewareToGroup(), middlewareGroup('nome', [...])
}
```

Nas rotas, um nome pode ser um alias, um grupo (`web`, `api`) ou uma classe; repetições são ignoradas.

## Middlewares incluídos

| Alias / classe | Âmbito | Faz |
|---|---|---|
| `TrimStrings` | global | `trim()` em inputs string (query, corpo e JSON); `''` → `null` se `app.convert_empty_strings_to_null` (excepto `password`, `password_confirmation`) |
| `SecurityHeaders` | global | headers de segurança (`app.security_headers`) |
| `StartSession` | grupo `web` | inicia a sessão pela cookie, grava no fim, envia a cookie, guarda o URL anterior |
| `VerifyCsrfToken` (`csrf`) | grupo `web` | valida `_token` / `X-CSRF-TOKEN` / `X-XSRF-TOKEN` em POST/PUT/PATCH/DELETE → `419` |
| `ShareErrorsFromSession` | grupo `web` | `$errors` disponível nas views (e em `$request->attribute('errors')`) |
| `SubstituteBindings` | todas as rotas | route-model binding (`UrlRoutable`) |
| `HandleCors` (`cors`) | grupo `api` | CORS a partir de `config/cors.php`; responde ao preflight com `204` |
| `auth`, `guest`, `ability:` | — | package `slendie/auth` |

### Excluir rotas do CSRF

Cria uma subclasse com `$except` (padrões de caminho sem `/` inicial, `*` como wildcard) e regista-a no lugar da original:

```php
final class VerifyCsrfToken extends \Slendie\Http\Middleware\VerifyCsrfToken
{
    protected array $except = ['webhooks/*'];
}

// bootstrap/app.php → withMiddleware(function (Kernel $kernel) { ... })
$kernel->getRouter()->middlewareGroup('web', [
    Slendie\Http\Middleware\StartSession::class,
    App\Http\Middleware\VerifyCsrfToken::class,        // a tua subclasse no lugar da original
    Slendie\Http\Middleware\ShareErrorsFromSession::class,
]);
$kernel->aliasMiddleware('csrf', App\Http\Middleware\VerifyCsrfToken::class);
```

### CORS

`config/cors.php`: `paths` (padrões, por omissão `api/*`), `allowed_origins` (aceita `*` e padrões como `https://*.site.pt`),
`allowed_methods`, `allowed_headers`, `exposed_headers`, `max_age`, `supports_credentials`. Com credenciais activas e
`allowed_origins = ['*']`, a origem do pedido é ecoada (o browser rejeita `*` com credenciais).

## Ordem de execução

Globais → middleware do grupo da rota (`web`/`api`) e da própria rota → `Controller::middleware()` → `SubstituteBindings`
→ acção. A resposta percorre o caminho inverso. Excepções lançadas (404, 419, ...) sobem até ao `Kernel`, que as passa ao
`Handler`.
