# core-04-11 — Skeleton `slendie/slendie`

> Peça 11 · Depende de: 01–10 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §3.3; análise crítica §1 (caso dos 90%)

## Objectivo
O projecto que o utilizador recebe com `composer create-project slendie/slendie meu-site`: landing page bonita e
leve (sem Node), formulário de contacto que grava `Lead` com validação e honeypot, página de obrigado, páginas
legais, 404 personalizada, testes a passar, `AGENTS.md`, README. Em 5 comandos está no ar. Painel de leads **não**
está aqui (é `slendie/auth` + `slendie/react-admin`, ou Blade à escolha do utilizador); mas a API JSON `/api/leads`
protegida por token simples **está**, para o react-admin consumir.

## Estrutura final (`skeleton/`)
```
app/
  Console/Commands/.gitkeep
  Http/Controllers/Controller.php            # abstract, extends Slendie\Http\Controller
  Http/Controllers/HomeController.php        # index(), thanks()
  Http/Controllers/ContactController.php     # store(StoreLeadRequest)
  Http/Controllers/Api/LeadController.php    # index(), show(), update() (status), destroy()
  Http/Middleware/.gitkeep
  Http/Requests/StoreLeadRequest.php
  Models/Lead.php                            # fillable, casts, scope new(), accessor initials
  Providers/AppServiceProvider.php
bootstrap/app.php  bootstrap/providers.php  bootstrap/cache/.gitignore
config/app.php  database.php  session.php  cors.php  logging.php  view.php  mail.php? (não; só quando package mail existir)
database/migrations/2026_01_01_000000_create_leads_table.php
database/seeders/DatabaseSeeder.php  LeadSeeder.php
database/.gitignore                           # *.sqlite
public/index.php  .htaccess  robots.txt  favicon.svg  css/app.css  js/app.js  img/.gitkeep
resources/views/layouts/app.blade.php
resources/views/partials/{head,nav,footer,flash}.blade.php
resources/views/home.blade.php  thanks.blade.php  privacy.blade.php  terms.blade.php
resources/views/components/… (não: sem components; usar partials com @include)
resources/views/errors/{404,500}.blade.php    # sobrepõem as da framework, com o layout do site
routes/web.php  api.php  console.php
storage/app/.gitkeep  storage/framework/{cache,sessions,views}/.gitkeep  storage/logs/.gitkeep  storage/.gitignore
tests/Pest.php  TestCase.php  Feature/HomeTest.php  Feature/ContactTest.php  Feature/Api/LeadApiTest.php
.env.example  .gitignore  .editorconfig  .gitattributes
composer.json  phpunit.xml  phpstan.neon  pint.json
slendie                                        # CLI
AGENTS.md  README.md  LICENSE  CHANGELOG.md
```

## Conteúdo chave
- `composer.json`: `name slendie/slendie`, `type project`, `require: php ^8.5, slendie/framework ^4.0`, `require-dev: pestphp/pest ^4.0, phpstan/phpstan ^2.1, laravel/pint ^1.26`, `autoload App\\ => app/, Database\\Seeders\\ => database/seeders/`, `autoload-dev Tests\\ => tests/`, scripts da peça 09 + `test`, `analyse`, `lint`, `fix`, `serve`; `config.allow-plugins pestphp/pest-plugin: true`; `extra.slendie.dont-discover: []`
- `.env.example`:
  ```
  APP_NAME="Slendie"
  APP_ENV=local
  APP_KEY=
  APP_DEBUG=true
  APP_URL=http://localhost:8000
  APP_LOCALE=pt
  APP_TIMEZONE=Europe/Lisbon

  DB_CONNECTION=sqlite
  # DB_HOST=127.0.0.1  DB_PORT=3306  DB_DATABASE=slendie  DB_USERNAME=root  DB_PASSWORD=

  SESSION_DRIVER=file
  SESSION_LIFETIME=120

  LOG_CHANNEL=daily
  LOG_LEVEL=debug

  # Token para a API /api/leads (Bearer). Gera um com: php slendie key:generate --show
  API_TOKEN=
  ```
- `routes/web.php`: `home`, `contact.store` (POST), `thanks`, `privacy`, `terms`; `Route::fallback` → `errors.404`
- `routes/api.php`: grupo `middleware('api.token')` com `apiResource('leads', Api\LeadController::class)->only(['index','show','update','destroy'])`; middleware `App\Http\Middleware\VerifyApiToken` (compara `bearerToken()` com `env('API_TOKEN')` via `hash_equals`; vazio → 503 "API desactivada") — simples, sem BD, suficiente para o painel React até o package auth trazer `TokenGuard`. **Nota:** quando `slendie/auth` está instalado, o react-admin troca para `auth:api`
- `Lead` model: `fillable name,email,phone,message,source,status,ip,user_agent`; `casts`: nenhum especial; `scopeStatus($q, $s)`, `scopeSearch($q, $term)` (whereLike em name/email); `getInitialsAttribute`
- `StoreLeadRequest`: regras da peça 08 + honeypot `website` (`prohibited`) + `consent` (`accepted`) para RGPD; mensagens pt
- `ContactController@store`: valida, cria Lead com `ip`/`user_agent`/`source = 'landing'`, flash `success`, redirect `thanks`; se `expectsJson` → 201 JSON (permite fetch sem recarregar)
- Views: layout com `<html lang="{{ app()->getLocale() }}">`, meta description, OG tags configuráveis em `config/app.php` (`seo.title`, `seo.description`, `seo.image`), `@stack('head')`/`@stack('scripts')`, `partials.flash` para `success`/`error`; `home` com hero, 3 benefícios, formulário (`@csrf`, `old()`, `@error`, honeypot escondido por CSS, checkbox consent), footer com links privacy/terms; CSS próprio em `public/css/app.css` (~200 linhas, variáveis CSS, dark mode via `prefers-color-scheme`, responsivo, sem framework); `public/js/app.js` (~40 linhas: envio do formulário por fetch com fallback)
- `AGENTS.md` (gerado de `framework/stubs/AGENTS.md.stub` no `post-create-project-cmd` **ou** incluído directamente — **decisão**: incluído directamente no skeleton; o stub serve para `slendie ai:agents-file --force` regenerar): o que a framework **tem** e **não tem** (sem Eloquent completo, sem Artisan, subconjunto Blade), comandos, convenções de pastas, como correr testes, como consultar `php slendie ai:context --json`
- `README.md` do skeleton: instalação em 5 comandos, estrutura, como adicionar painel (`composer require slendie/auth slendie/react-admin`), deploy (Apache `.htaccess` incluído; Nginx snippet; `public/` como docroot; permissões de `storage/`; `APP_DEBUG=false`), FAQ
- `tests/Feature/HomeTest` (200, vê título), `ContactTest` (válido grava e redirecciona; inválido devolve erros; honeypot preenchido → 422/redirect sem gravar; JSON 201), `Api/LeadApiTest` (sem token 401; token errado 401; token vazio 503; index pagina; update status; destroy)
- `phpstan.neon` nível 6 para a app (mais leve que a framework), `pint.json` psr12

## Setup automático (`composer create-project`)
`post-create-project-cmd`: copia `.env`, `key:generate`, cria `database/database.sqlite`, `migrate --force`, imprime próximos passos (`php slendie serve`). Deve funcionar em Windows (PowerShell) e Unix — usar só `@php` scripts, nunca `cp`/`touch`.

## Testes
Os do skeleton (acima) + um teste no monorepo (`tests/Skeleton/CreateProjectTest.php`, marcado `->group('slow')`) que corre `composer create-project --repository='{"type":"path","url":"skeleton"}' slendie/slendie /tmp/x --no-interaction` e verifica `php slendie about` e `composer test` no projecto criado.

## Critérios de aceitação
```
composer create-project slendie/slendie meu-site
cd meu-site
php slendie serve
```
→ landing page em `http://127.0.0.1:8000`, submeter formulário grava lead e mostra obrigado; `composer test` verde; `php slendie ai:context` descreve o projecto; Lighthouse ≥ 95 em performance/acessibilidade/SEO para a home (informativo).
`docs/installation.md`, `docs/structure.md`, `docs/deployment.md`, `docs/landing-page.md` (tutorial do caso dos 90%) escritos.

## Desvios
- **Rotas:** `contact.store` é `POST /contact`; a página de obrigado é `/obrigado` (`thanks`), as legais `/privacidade` e `/termos` (`privacy`, `terms`). Sem `Route::fallback`: o `Handler` já renderiza `errors::404` e o skeleton sobrepõe-a em `resources/views/errors/404.blade.php` (e `500`), com o layout do site. Essas views não podem usar `session()`/`request()` sem guarda — os 404 não passam pelo grupo `web` (sem sessão) — por isso o `partials/flash` lê a sessão só se existir.
- **`apiResource`:** a framework recebe `['except' => ['store']]` (não existe `->only()` encadeado; `apiResource` devolve a lista de rotas).
- **`Lead::filtered($status, $search)`** (novo): a API usa-o em vez de `Lead::query()->status()->search()`, porque o PHPStan não vê os scopes mágicos do `Model\Builder` e a regra do projecto é não suprimir erros. Os scopes `status`, `new` e `search` continuam disponíveis. O `search` usa `whereLike` num grupo: a closure recebe o `Database\Query\Builder`, não o `Model\Builder`.
- **`php slendie ai:agents-file`** (não existia, apesar de `docs/ai-agents.md` o prometer): novo `AiAgentsFileCommand` + `framework/stubs/AGENTS.md.stub` (`{{ name }}` = `app.name`; o stub do projecto em `stubs/` tem prioridade). O `AGENTS.md` do skeleton é esse stub com `Slendie`, incluído directamente (como decidido). 3 testes no core.
- **`post-create-project-cmd`:** além de `.env`, `key:generate` e `migrate --force`, cria `database/database.sqlite` com `touch()` dentro de `php -r` (portável) e imprime o próximo passo. Sem `bin/setup`.
- **`config/view.php`** acrescentado (`paths`, `compiled`); `config/app.php` ganha `seo.{title,description,image}` (env `SEO_*`) e `providers` vazio — o `AppServiceProvider` regista-se em `bootstrap/providers.php`. `mail.php` não existe (sem package mail).
- **Formulário:** campo `consent` com `accepted`; `ContactController` redirecciona para `/obrigado` com flash `success` ou devolve `201 {message,id}` em JSON. `source = 'landing'`. Os 422 em JSON usam as mensagens pt do `StoreLeadRequest`.
- **Segurança:** `VerifyApiToken` devolve 401 com `WWW-Authenticate: Bearer` e 503 com `API_TOKEN` vazio; `public/.htaccess` repassa o cabeçalho `Authorization` ao PHP (senão o Apache o apaga e o token nunca chegava).
- **`.gitattributes`:** sem `export-ignore` para `tests/` — o `composer create-project` espelha o archive git e o projecto ficava sem testes (`composer test` falhava com "test directory does not exist"; apanhado pelo `CreateProjectTest`).
- **Limpeza:** removidos do índice do skeleton as sessões e views compiladas que tinham sido commitadas por engano (a regra do `.gitignore` não deixava passar os `.gitkeep`; agora `!/storage/framework/*/.gitkeep`) e o `.env.testing.local`. O teste `framework/tests/Feature/Validation/ContactFormTest.php` foi apagado: acoplava-se ao `ContactController` antigo do skeleton (peça 08) e os mesmos cenários (validação, `old()`, honeypot, JSON) estão agora em `skeleton/tests/Feature/ContactTest.php`.
- **Monorepo:** `tests/Skeleton/CreateProjectTest.php` (grupo `slow`, excluído do `composer test` em `phpunit.xml`; corre com `composer test:slow`). Faz `composer create-project --stability=dev --add-repository` a partir de uma cópia do skeleton com `minimum-stability: dev` (o publicado exige `slendie/framework ^4.0` estável; no monorepo só há `4.x-dev`) e da framework por path (sem `--add-repository` o Composer ia buscar a framework ao Packagist), depois `php slendie about` e `composer test` no projecto criado. Precisa de rede (Pest, PHPStan, Pint). Os pipes do `proc_open` partilham stdout/stderr: o Composer escreve muito em stderr e dois pipes lidos em sequência bloqueavam o teste.
- **Qualidade (resultado):** no projecto criado, `composer test` = 20 testes / 84 asserções verdes, `phpstan` nível 6 sem erros (as `routes/console.php` ficam de fora: o `$this` das closures é injectado em runtime), `pint --test` limpo. Core: 1379 testes verdes, PHPStan nível 8 e Pint limpos.
- **Não verificado:** Lighthouse (informativo); o CSS/JS não foi testado em browsers; `docs/deployment.md` não foi exercitado num servidor real (Apache/Nginx); só corri no Windows 11 / PHP 8.5.4 (Laragon), não em Linux.
