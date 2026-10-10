# Sessão

Disponível nas rotas do grupo `web` (middleware `StartSession`). O id viaja numa cookie (`slendie_session`) e os dados
ficam num handler: `file` (ficheiros em `storage/framework/sessions`) ou `array` (memória, para testes). Não usa `$_SESSION`.

```php
session()->put('key', 'value');       // também put(['a' => 1, 'b' => 2])
session()->get('key', 'default');
session('key');                       // atalho; session(['key' => 'value']) escreve
session()->has('key');                // existe e não é null
session()->exists('key');             // existe (mesmo null)
session()->forget('key');  session()->flush();
session()->pull('key');               // lê e apaga
session()->push('list', 'item');
session()->increment('visits');  session()->decrement('visits');
session()->all();

session()->flash('success', 'Guardado!');   // só no próximo pedido
session()->now('x', 'y');                   // só neste pedido
session()->reflash();  session()->keep(['x']);

session()->regenerate();              // novo id, mantém dados (faz-se no login); regenerate(true) apaga o id antigo
session()->invalidate();              // apaga tudo e regenera o id
session()->token();                   // token CSRF
session()->regenerateToken();
session()->previousUrl();             // último GET não-AJAX (usado por back())
```

`session()` (e `request()->session()`) lança `RuntimeException` se a rota não passou pelo `StartSession`.

## Flash e input antigo

O flash dura exactamente um pedido: gravado no pedido N, lido no N+1, apagado no fim do N+1 (o *ageing* acontece em `save()`).
`flashInput()` guarda o input; lê-se com `old('campo')` ou `session()->getOldInput('campo')`.

Nas views, as mensagens flash:

```blade
@if (session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
```

## Ciclo de vida

`StartSession` cria uma `Session` por pedido, lê-a do handler, e grava-a no fim do `handle()` (mesmo que a acção lance
uma excepção), acrescentando a cookie à resposta. Gravar *antes* de enviar evita que o redirect seguinte chegue antes dos
dados. Ocasionalmente (2% dos pedidos) apaga as sessões expiradas (`gc`). O id recebido por cookie só é aceite se tiver 40
caracteres alfanuméricos; senão é gerado outro.

## Configuração (`config/session.php`)

| Chave | Default |
|---|---|
| `driver` | `file` (`SESSION_DRIVER`; `array` para testes) |
| `lifetime` | `120` (minutos, `SESSION_LIFETIME`) |
| `files` | `null` → `storage/framework/sessions` |
| `cookie` | `slendie_session` |
| `path`, `domain` | `/`, `null` |
| `secure` | `null` (auto: `true` em HTTPS) |
| `http_only` | `true` |
| `same_site` | `lax` |

A framework traz estes valores por omissão; o ficheiro do projecto só precisa de indicar o que muda.

Outros handlers: regista um `SessionHandlerInterface` no container como `session.handler`
(`$this->app->singleton('session.handler', fn () => new MeuHandler())`).

## Em testes

```php
config(['session.driver' => 'array']);          // antes da primeira resolução de 'session.handler'
$session = new Slendie\Session\Session('s', new Slendie\Session\ArraySessionHandler());
$session->start();
```
