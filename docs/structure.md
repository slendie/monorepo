# Estrutura do projecto

```
meu-site/
├── app/
│   ├── Console/Commands/        # comandos próprios (descobertos automaticamente)
│   ├── Http/
│   │   ├── Controllers/         # controllers (namespace App\Http\Controllers); Api/LeadController para a API
│   │   ├── Middleware/          # middlewares da aplicação (VerifyApiToken protege /api/leads)
│   │   └── Requests/            # FormRequests (validação)
│   ├── Models/                  # models (App\Models)
│   └── Providers/               # AppServiceProvider e outros
├── bootstrap/
│   ├── app.php                  # cria a Application (rotas, middleware, excepções)
│   ├── providers.php            # lista de providers da app
│   └── cache/                   # cache de descoberta de packages (gerada)
├── config/                      # app.php, database.php, session.php, cors.php, logging.php, view.php
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── database.sqlite          # BD por defeito (gitignored)
├── public/                      # document root: index.php, .htaccess, css/, js/, img/
├── resources/views/             # templates .blade.php: layouts/, partials/ (head, nav, footer, flash), errors/ (404, 500)
├── routes/
│   ├── web.php                  # rotas com sessão e CSRF (grupo web)
│   ├── api.php                  # rotas JSON, prefixo /api (grupo api)
│   └── console.php              # comandos em closure
├── storage/
│   ├── app/                     # uploads e ficheiros da app
│   ├── framework/{cache,sessions,views}/
│   └── logs/
├── tests/                       # Pest: Feature/ e Unit/
├── .env  .env.example
├── composer.json  phpunit.xml  phpstan.neon  pint.json
├── slendie                      # CLI: php slendie ...
└── AGENTS.md                    # convenções para agentes de IA
```

## Onde fica o quê

| Quero… | Vai para… |
|---|---|
| Uma nova página | `routes/web.php` + controller em `app/Http/Controllers` + view em `resources/views` |
| Um endpoint JSON | `routes/api.php` + controller em `app/Http/Controllers/Api` |
| Uma tabela nova | `php slendie make:model Nome -m` → `app/Models/Nome.php` + `database/migrations/…` |
| Proteger rotas | middleware em `app/Http/Middleware` + alias em `bootstrap/app.php` |
| Validar um formulário | `php slendie make:request StoreNomeRequest` ou `$request->validate([...])` |
| Registar algo no arranque | `app/Providers/AppServiceProvider.php` |
| Um comando CLI | `php slendie make:command NomeCommand` |

## Namespaces

- `App\` → `app/` (PSR-4)
- `Database\Seeders\` → `database/seeders/`
- `Tests\` → `tests/`
- `Slendie\` → `vendor/slendie/framework/src/` (a framework; não editar)

## Permissões

`storage/` e `bootstrap/cache/` têm de ser graváveis pelo servidor web.
