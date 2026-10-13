# Logging

O `Logger` tem a forma do PSR-3 (sem a dependência): oito níveis, interpolação `{chave}` e vários canais.

```php
use Slendie\Support\Facades\Log;

Log::info('Lead criado', ['id' => $lead->id]);
Log::warning('Quota a {percent}%', ['percent' => 90]);     // "Quota a 90%"
Log::error('Falhou', ['exception' => $e]);                 // classe, mensagem, ficheiro:linha e trace
Log::channel('stderr')->debug('x');                        // só neste canal
logger('mensagem de debug', ['k' => 'v']);                 // atalho; sem argumentos devolve o Logger
logger()->notice('olá');
report($e);                                                // regista via Handler (respeita dontReport) sem interromper
```

Níveis, do menos ao mais grave: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`.
Também existe `Log::log('info', $mensagem, $contexto)`.

## Formato da linha

```
[2026-10-13 14:03:22.123] local.ERROR: mensagem {"id":7}
[exception] RuntimeException: boom in /app/Services/X.php:42
[stacktrace]
#0 /app/Http/Controllers/Y.php(10): App\Services\X->run()
```

- `local` é o ambiente (`APP_ENV`); o JSON final é o contexto (sem a `exception`, que vai por extenso em baixo).
- Excepções encadeadas acrescentam uma linha `[previous] Classe: mensagem in ficheiro:linha`.
- `{chave}` na mensagem é substituída por valores escalares (`true`/`false`/`null` por extenso).

## `config/logging.php`

```php
'default' => env('LOG_CHANNEL', 'daily'),
'channels' => [
    'single' => ['driver' => 'single', 'path' => storage_path('logs/slendie.log'), 'level' => 'debug'],
    'daily'  => ['driver' => 'daily',  'path' => storage_path('logs/slendie.log'), 'days' => 14],
    'stderr' => ['driver' => 'stderr'],
    'null'   => ['driver' => 'null'],
    'stack'  => ['driver' => 'stack', 'channels' => ['daily', 'stderr']],
],
```

| Driver | Faz |
|---|---|
| `single` | um só ficheiro (`storage/logs/slendie.log`) |
| `daily` | um ficheiro por dia, `slendie-2026-10-13.log`; apaga os que tenham mais de `days` dias (por omissão 14) |
| `stderr` | escreve no `stderr` (contentores, `php slendie serve`) |
| `null` | descarta tudo |
| `stack` | escreve em vários canais; cada um aplica o seu `level` |

`level` (opcional, por omissão `debug`) filtra: só regista esse nível ou mais grave. A framework traz estes canais por
omissão (o `default` é `single`); o skeleton usa `daily`. O ficheiro do projecto sobrepõe a configuração da framework
(merge de 1.º nível: se definires `channels`, defines todos).

Sem `config/logging.php` e sem canais, o canal implícito é o `single` em `storage/logs/slendie.log`.

## Drivers personalizados

Num service provider:

```php
Log::extend('slack', function (array $config) {
    return fn (string $line, string $level) => Http::post($config['webhook'], ['text' => $line]);
});
```

e em `config/logging.php`: `'slack' => ['driver' => 'slack', 'webhook' => env('SLACK_WEBHOOK'), 'level' => 'error']`.
O `Closure` recebe a configuração do canal e devolve um `callable(string $line, string $level): void` ou um objecto com
`write(string $line, string $level)`.

## Erros da aplicação

O `Handler::report()` chama `Log::error($e->getMessage(), ['exception' => $e])` para tudo o que não esteja em
`dontReport` (por omissão `HttpException` e `ValidationException`). Se o logging falhar, o erro é engolido: registar
nunca pode causar um novo erro. Ver [Erros](errors.md).
