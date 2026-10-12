# Erros e logging

## Páginas de erro

Com `APP_DEBUG=true`: página de debug com excepção, excerto de código, stack trace, request e `.env` sanitizado.

Com `APP_DEBUG=false`: `resources/views/errors/{404,403,419,422,500}.blade.php` se existirem no projecto; senão
as páginas da framework. Pedidos que esperam JSON recebem `{"message": "..."}`.

| Situação | Status |
|---|---|
| Rota não encontrada / `ModelNotFoundException` / `abort(404)` | 404 |
| Método não permitido | 405 + `Allow` |
| Token CSRF inválido | 419 |
| `ValidationException` | 422 (JSON) ou redirect |
| Qualquer outra excepção | 500 |

```php
abort(404);
abort(403, 'Sem acesso');
abort_if($lead->user_id !== $user->id, 403);
throw new HttpException(402, 'Pagamento necessário');
```

## Personalizar o Handler

Em `bootstrap/app.php`:

```php
->withExceptions(function (Handler $handler) {
    $handler->dontReport([MyQuietException::class]);
    $handler->report(fn (PaymentException $e) => Payments::notify($e));
    $handler->render(fn (PaymentException $e, Request $request) => response()->view('errors.payment', [], 402));
})
```

## Logging

```php
use Slendie\Support\Facades\Log;

Log::info('Lead criado', ['id' => $lead->id]);
Log::error('Falhou', ['exception' => $e]);
Log::channel('stderr')->debug('x');
logger()->warning('y');
report($e);                               // regista sem interromper
```

Níveis: `emergency`, `alert`, `critical`, `error`, `warning`, `notice`, `info`, `debug`.

### `config/logging.php`

```php
'default' => env('LOG_CHANNEL', 'daily'),
'channels' => [
    'single' => ['driver' => 'single', 'path' => storage_path('logs/slendie.log'), 'level' => 'debug'],
    'daily'  => ['driver' => 'daily', 'path' => storage_path('logs/slendie.log'), 'days' => 14],
    'stderr' => ['driver' => 'stderr'],
    'stack'  => ['driver' => 'stack', 'channels' => ['daily', 'stderr']],
    'null'   => ['driver' => 'null'],
],
```

Formato: `[2026-10-10 14:03:22.123] local.ERROR: mensagem {"id":5}` seguido do trace quando há excepção.

Packages podem adicionar drivers com `Log::extend('slack', fn ($config) => new SlackLogger($config))`.
