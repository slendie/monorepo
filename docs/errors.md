# Erros e logging

Toda a excepção lançada durante um pedido é apanhada pelo `Kernel`, registada (`Handler::report()`) e convertida numa
`Response` (`Handler::render()`). Nada escapa para o PHP.

## Páginas de erro

Com `APP_DEBUG=true`: página de debug com a classe da excepção, mensagem, `ficheiro:linha`, excerto de código (±10 linhas),
stack trace, dados do pedido e ambiente (`.env`) sanitizado — chaves que contenham `KEY`, `SECRET`, `PASSWORD`, `TOKEN` ou
`CREDENTIAL` aparecem como `••••`, tal como os headers `Authorization`/`Cookie` e campos `password` do pedido.

Com `APP_DEBUG=false`: `resources/views/errors/{status}.blade.php` se existir no projecto; senão a página da framework
(`404`, `403`, `419`, `500`, ou `generic` para os outros estados). Pedidos que esperam JSON (`Accept: application/json`,
XHR ou rota `/api/*`) recebem `{"message": "..."}`; em erros 500 sem debug a mensagem é `Server Error`.

> As páginas são views Blade no namespace `errors::` (`errors::404`, `errors::generic`, `errors::debug`, e o layout `errors::layout`)
> com as variáveis `$status`, `$title` e `$message`. O projecto sobrepõe qualquer uma criando
> `resources/views/errors/{nome}.blade.php` (que pode estender `errors::layout`). Se a própria renderização da página falhar,
> o Handler cai para um HTML mínimo em vez de causar outro erro.

| Situação | Status |
|---|---|
| Rota não encontrada / `abort(404)` / route-model binding sem resultado | 404 |
| Método não permitido | 405 + `Allow` |
| Token CSRF inválido | 419 |
| `ValidationException` | 422 (JSON) ou redirect (peça 08) |
| Qualquer outra excepção | 500 |

```php
abort(404);
abort(403, 'Sem acesso');
abort(429, 'Calma', ['Retry-After' => '60']);
abort_if($lead->user_id !== $user->id, 403);
abort_unless($user->isAdmin(), 403);
throw new HttpException(402, 'Pagamento necessário');     // Slendie\Exceptions\HttpException
```

Excepções disponíveis: `HttpException`, `NotFoundHttpException`, `MethodNotAllowedHttpException`, `TokenMismatchException`
(todas com `getStatusCode()`/`getHeaders()`) e `UrlGenerationException`.

## Personalizar o Handler

Em `bootstrap/app.php`:

```php
->withExceptions(function (Handler $handler) {
    $handler->dontReport([MyQuietException::class]);                   // não vai para o log
    $handler->renderable(PaymentException::class, fn (PaymentException $e, Request $request) =>
        response('Pagamento necessário', 402));                        // resposta própria por tipo
})
```

`HttpException` já não é registada no log por omissão. Um renderer que devolva algo que não seja `Response` é ignorado e
o tratamento normal continua. Para alterações maiores, estende o `Handler` e regista-o:
`$this->app->singleton(Slendie\Foundation\Exceptions\Handler::class, App\Exceptions\Handler::class)`.

Excepções fora do `Kernel` (por exemplo em `public/index.php` antes do `handle`, ou na consola) passam pelo
`HandleExceptions`: na web usam o mesmo `render()`; na CLI `renderForConsole()` escreve texto simples (com trace em debug).

## Logging

Por agora o `Handler::report()` escreve em `storage/logs/slendie.log`:

```
[2026-10-10 14:03:22] local.ERROR: mensagem {"exception":"RuntimeException","file":"...","line":12}
#0 /caminho/App.php(10): ...
```

O `Logger` completo — facade `Log`, canais (`single`, `daily`, `stderr`, `stack`, `null`), `logger()` e `report($e)` — chega
com a peça de packages/logging (`core-04-09`). A configuração e a API abaixo descrevem o alvo final:

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

Packages podem adicionar drivers com `Log::extend('slack', fn ($config) => new SlackLogger($config))`.
