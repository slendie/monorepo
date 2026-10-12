# Middleware

```bash
php slendie make:middleware EnsureIsAdmin
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

Um middleware pode receber **parâmetros** (`'throttle:10,1'`):

```php
public function handle(Request $request, Closure $next, string $max, string $minutes): Response
```

E pode ter `terminate(Request $request, Response $response): void`, chamado depois da resposta ser enviada.

## Registo

Em `bootstrap/app.php`:

```php
->withMiddleware(function (Kernel $kernel) {
    $kernel->append(TrimStrings::class);                       // global
    $kernel->appendToGroup('web', MyWebMiddleware::class);
    $kernel->alias('admin', EnsureIsAdmin::class);             // Route::...->middleware('admin')
})
```

Ou num Service Provider (é o que os packages fazem):

```php
$this->router()->aliasMiddleware('admin', EnsureIsAdmin::class);
$this->router()->pushMiddlewareToGroup('web', X::class);
```

## Middlewares incluídos

| Alias / classe | Grupo | Faz |
|---|---|---|
| `TrimStrings` | global | `trim()` em inputs string; `''` → `null` |
| `SecurityHeaders` | global | headers de segurança |
| `StartSession` | web | inicia sessão, gere flash |
| `VerifyCsrfToken` (`csrf`) | web | valida `_token` / `X-CSRF-TOKEN` em POST/PUT/PATCH/DELETE → 419 |
| `ShareErrorsFromSession` | web | `$errors` disponível nas views |
| `SubstituteBindings` | web, api | route-model binding |
| `HandleCors` (`cors`) | api | CORS a partir de `config/cors.php` |
| `auth`, `guest`, `ability:` | — | package `slendie/auth` |

### Excluir rotas do CSRF

```php
$kernel->validateCsrfTokens(except: ['webhooks/*']);
```

## Ordem de execução

Globais → grupo da rota (`web`/`api`) → middlewares da rota → `Controller::middleware()` → acção. A resposta percorre
o caminho inverso.
