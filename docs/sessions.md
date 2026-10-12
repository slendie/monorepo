# Sessão

Disponível nas rotas do grupo `web`. Driver `file` (ficheiros em `storage/framework/sessions`) ou `array` (testes).

```php
session()->put('key', 'value');
session()->get('key', 'default');
session('key');                       // atalho
session()->has('key');  session()->exists('key');
session()->forget('key');  session()->flush();
session()->pull('key');               // lê e apaga
session()->push('list', 'item');
session()->increment('visits');
session()->all();

session()->flash('success', 'Guardado!');   // só no próximo pedido
session()->now('x', 'y');                   // só neste pedido
session()->reflash();  session()->keep(['x']);

session()->regenerate();              // novo id (faz-se no login)
session()->invalidate();              // destrói e regenera
session()->token();                   // token CSRF
```

Nas views, as mensagens flash:

```blade
@if (session('success'))
    <div class="alert">{{ session('success') }}</div>
@endif
```

## Configuração (`config/session.php`)

| Chave | Default |
|---|---|
| `driver` | `file` |
| `lifetime` | `120` (minutos) |
| `expire_on_close` | `false` |
| `files` | `storage/framework/sessions` |
| `cookie` | `slendie_session` |
| `path`, `domain` | `/`, `null` |
| `secure` | `null` (auto: `true` em HTTPS) |
| `http_only` | `true` |
| `same_site` | `lax` |

Packages podem registar outros drivers com `Session::extend('redis', fn () => new Handler())`.
