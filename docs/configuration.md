# Configuração e `.env`

## `.env`

Variáveis de ambiente ficam em `.env` (nunca commitado) com exemplo em `.env.example`. Formato suportado:

```
APP_NAME="Meu Site"          # aspas para espaços
APP_DEBUG=true               # true/false/null convertidos
APP_URL=http://localhost:8000
DB_DATABASE=${APP_NAME}.sqlite   # interpolação simples
# comentário
export LEGACY=1              # 'export' é ignorado
```

Se existir `.env.{APP_ENV}` (ex.: `.env.testing`), tem prioridade sobre `.env`.

Lê com `env('APP_NAME', 'default')` **apenas dentro de `config/*.php`**. No resto do código usa `config()`.

## `config/*.php`

Cada ficheiro devolve um array. Acesso por *dot notation*:

```php
config('app.name');                       // "Meu Site"
config('database.connections.mysql.host');
config(['app.debug' => false]);           // escrever em runtime
config('app.seo', []);                    // default
```

### `config/app.php`
| Chave | Default | Descrição |
|---|---|---|
| `name` | `env('APP_NAME', 'Slendie')` | Nome da aplicação |
| `env` | `env('APP_ENV', 'production')` | `local`, `testing`, `production` |
| `debug` | `env('APP_DEBUG', false)` | Página de debug e erros detalhados |
| `url` | `env('APP_URL', 'http://localhost')` | Base para `url()`/`route()` em CLI |
| `timezone` | `env('APP_TIMEZONE', 'UTC')` | |
| `locale` / `fallback_locale` | `pt` / `en` | Mensagens de validação |
| `key` | `env('APP_KEY')` | Assinatura de cookies e tokens; gera com `php slendie key:generate` |
| `providers` | `[]` | Providers adicionais (além de `bootstrap/providers.php`) |
| `security_headers` | array | Headers enviados pelo middleware `SecurityHeaders` |
| `seo` | array | `title`, `description`, `image` usados pelo layout do skeleton |

### Outros ficheiros
- `database.php` — ver [database.md](database.md)
- `session.php` — driver (`file`, `array`), lifetime, cookie, `same_site`
- `cors.php` — paths, origens, métodos, headers (aplicado ao grupo `api`)
- `logging.php` — canais `single`, `daily`, `stderr`, `stack`, `null`
- `view.php` — `paths` e `compiled`

## Ambientes

```php
app()->environment();              // 'local'
app()->environment('production');  // bool
app()->isDebug();
app()->runningInConsole();
```

Em `testing` o middleware CSRF é desactivado e a sessão usa o driver `array`.

## Helpers de caminhos

`base_path()`, `app_path()`, `config_path()`, `database_path()`, `public_path()`, `resource_path()`,
`storage_path()`, `lang_path()` — todos aceitam um sufixo: `storage_path('logs/x.log')`.
