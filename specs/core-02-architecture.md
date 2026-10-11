# Slendie v4 — Arquitectura

> Estado: aprovado para implementação · Data: 2026-10-10
> Decisões fixadas com o utilizador: React em package opcional · dois packages em monorepo · Auth em package opcional · PHP ≥ 8.5 · nome `slendie/*`, namespace `Slendie\` · documentação em pt-PT · testes com Pest.

## 1. Visão

Slendie é um "Laravel Light": uma framework MVC em PHP, sem dependências em runtime, para landing pages com painel
de leads e sites simples renderizados em PHP. Quem conhece Laravel reconhece pastas, nomes e APIs; quem não conhece
tem uma framework que cabe na cabeça (core < 8 000 linhas).

**Regra de ouro:** uma funcionalidade só entra no core se o caso dos 90% (landing page + formulário + painel) a usar.
Tudo o resto é package.

## 2. Packages e repositórios

| Package Composer | Pasta no monorepo | Tipo | Conteúdo |
|---|---|---|---|
| `slendie/framework` | `framework/` | library | Todo o código `Slendie\*`. Zero deps em runtime. |
| `slendie/slendie` | `skeleton/` | project | Skeleton para `composer create-project`. Depende de `slendie/framework`. Traz landing page + formulário de contacto + model `Lead` como exemplo. |
| `slendie/auth` | `packages/auth/` | library | Login por sessão, model `User`, middlewares `auth`/`guest`, views de login, tokens para API. |
| `slendie/react-admin` | `packages/react-admin/` | library | Scaffold Vite + React do painel (lista de leads), publicado para o projecto com `php slendie react-admin:install`. |

Durante o desenvolvimento, o `composer.json` da raiz do monorepo liga tudo por `path` repositories. Na publicação,
cada pasta é um repositório Git próprio (split por `git subtree` ou GitHub Action `splitsh`).

**Repositórios GitHub (decisão):** o monorepo de desenvolvimento passa a chamar-se `slendie/monorepo` (renomear o
actual `slendie/slendie`, que mantém o histórico v1–v3 e as tags; o GitHub redirecciona o URL antigo). Os
repositórios espelho, só de leitura e gerados por split, são: `slendie/framework` (`framework/`), `slendie/slendie`
(`skeleton/`, novo repositório limpo), `slendie/auth` (`packages/auth/`) e `slendie/react-admin`
(`packages/react-admin/`). Nunca se faz commit directo nos espelhos.

Versionamento: SemVer. `slendie/framework` e `slendie/slendie` partilham o major (4.x). Packages seguem o seu próprio
ciclo mas declaram `"slendie/framework": "^4.0"`.

## 3. Estrutura de pastas

### 3.1 Monorepo (este repositório)

```
monorepo/                      # github.com/slendie/monorepo
├── composer.json              # dev-only: path repos para framework/, skeleton/, packages/*; scripts test/lint/analyse
├── README.md
├── .gitignore  .editorconfig  .github/workflows/ci.yml
├── docs/                      # documentação da framework (publicada em slendie.lcloss.dev)
├── specs/                     # planeamento e especificações de implementação
├── framework/                 # slendie/framework
├── skeleton/                  # slendie/slendie
└── packages/
    ├── auth/                  # slendie/auth
    └── react-admin/           # slendie/react-admin
```

### 3.2 `framework/` (namespace `Slendie\`)

```
framework/
├── composer.json              # php ^8.5, ext-pdo; autoload psr-4 Slendie\ => src/; files: src/Support/helpers.php
├── src/
│   ├── Foundation/
│   │   ├── Application.php            # extends Container; paths, providers, boot, handle(Request): Response
│   │   ├── ServiceProvider.php        # register(), boot(), helpers loadRoutesFrom/loadViewsFrom/loadMigrationsFrom/publishes/commands
│   │   ├── PackageDiscovery.php       # lê vendor/composer/installed.json → extra.slendie.providers
│   │   ├── Bootstrap/                 # LoadEnvironment, LoadConfiguration, HandleExceptions, RegisterProviders, BootProviders
│   │   └── Exceptions/Handler.php     # render(Throwable): Response (debug page / 404 / 500 / json)
│   ├── Container/
│   │   ├── Container.php              # bind, singleton, instance, make, call, autowiring por reflection
│   │   └── BindingResolutionException.php
│   ├── Http/
│   │   ├── Request.php                # capture(), method(), path(), input(), query(), json(), file(), header(), ip(), expectsJson(), validate()
│   │   ├── Response.php               # body, status, headers, send(); withHeader(), cookie()
│   │   ├── JsonResponse.php  RedirectResponse.php  ViewResponse.php
│   │   ├── UploadedFile.php
│   │   ├── Kernel.php                 # middleware global + aliases + grupos; handle(Request): Response
│   │   ├── Controller.php             # base vazia com helpers view()/redirect()/json()
│   │   └── Middleware/                # StartSession, VerifyCsrfToken, TrimStrings, SecurityHeaders, HandleCors, SubstituteBindings
│   ├── Routing/
│   │   ├── Router.php  Route.php  RouteCollection.php  RouteGroup.php
│   │   ├── Pipeline.php               # pipeline de middlewares (onion)
│   │   ├── UrlGenerator.php           # route('name', [...]), url(), asset()
│   │   └── Facade Route.php           # Route::get/post/put/patch/delete/any/match/group/resource/redirect/view
│   ├── Session/
│   │   ├── Session.php                # get/put/forget/flash/pull/regenerate/token
│   │   └── FileSessionHandler.php     # default; native PHP sessions com save_path em storage/framework/sessions
│   ├── View/
│   │   ├── Factory.php                # render('home', [...]), exists(), share(), composer()
│   │   ├── View.php
│   │   ├── Compiler/BladeCompiler.php # .blade.php → PHP em storage/framework/views
│   │   ├── Compiler/Directives/*.php  # uma classe por família de directivas; directive() para registo externo
│   │   └── Engine.php                 # include do ficheiro compilado com extract($data)
│   ├── Database/
│   │   ├── DatabaseManager.php        # connection('name'), query($sql,$params)->fetch()/fetchAll(), table('x')
│   │   ├── Connection.php             # PDO wrapper: select/insert/update/delete/statement/transaction, driver()
│   │   ├── Query/Builder.php          # select/where/whereLike/orWhere/whereIn/whereNull/whereBetween/join/orderBy/groupBy/limit/offset
│   │   │                              # get/fetchAll, first/fetch, value, count/exists, insert/insertGetId/update/delete, paginate, toSql
│   │   ├── Query/Grammar.php + Grammars/{Sqlite,MySql,Pgsql}Grammar.php
│   │   ├── Schema/Schema.php  Blueprint.php  Column.php  Grammars/*   # DDL por driver
│   │   ├── Migrations/Migration.php  Migrator.php  MigrationRepository.php
│   │   └── Seeders/Seeder.php
│   ├── Model/
│   │   ├── Model.php                  # table, primaryKey, fillable, guarded, casts, timestamps, hidden; hidratação; __callStatic → Builder
│   │   ├── Builder.php                # Query\Builder que hidrata Models; fetch()/first(), fetchAll()/get(), findOrFail
│   │   ├── Collection.php             # ArrayAccess, IteratorAggregate, Countable, JsonSerializable; map/filter/pluck/first/toArray
│   │   ├── Relations/{HasOne,HasMany,BelongsTo}.php
│   │   └── ModelNotFoundException.php
│   ├── Validation/
│   │   ├── Validator.php  Rules/*.php  ValidationException.php  MessageBag.php
│   ├── Console/
│   │   ├── Application.php  Command.php  Input.php  Output.php
│   │   └── Commands/                  # Serve, Migrate, MigrateRollback, MigrateStatus, MigrateFresh, DbSeed, MakeController, MakeModel,
│   │                                  # MakeMigration, MakeMiddleware, MakeProvider, MakeCommand, RouteList, ViewClear, KeyGenerate,
│   │                                  # VendorPublish, AiContext
│   ├── Support/
│   │   ├── Env.php  Config.php  Str.php  Arr.php  Vite.php  Facade.php
│   │   └── helpers.php                # env(), config(), app(), base_path(), storage_path(), public_path(), view(), route(), url(), asset(),
│   │                                  # redirect(), response(), request(), session(), old(), csrf_token(), csrf_field(), method_field(),
│   │                                  # abort(), e(), dd(), vite()
│   ├── Testing/
│   │   ├── TestCase.php               # cria Application com env testing, DB sqlite :memory:
│   │   ├── TestResponse.php           # assertOk/assertStatus/assertSee/assertDontSee/assertRedirect/assertJson/assertJsonPath
│   │   └── RefreshDatabase.php        # trait: migra antes de cada teste
│   └── Exceptions/                    # HttpException, NotFoundHttpException, MethodNotAllowedHttpException, TokenMismatchException
├── stubs/                             # templates para make:*
├── resources/views/errors/{404,500,debug}.blade.php
└── tests/                             # Pest; unitários por componente + feature com app em memória
```

### 3.3 `skeleton/` (o que o utilizador recebe com `composer create-project slendie/slendie`)

```
meu-projecto/
├── app/
│   ├── Http/Controllers/{Controller,HomeController,ContactController}.php
│   ├── Http/Middleware/.gitkeep
│   ├── Models/Lead.php
│   └── Providers/AppServiceProvider.php
├── bootstrap/
│   ├── app.php                        # cria Application, regista Kernel/Handler, devolve $app
│   └── providers.php                  # lista de providers da app
├── config/{app,database,session,view,mail?}.php
├── database/
│   ├── migrations/2026_01_01_000000_create_leads_table.php
│   ├── seeders/DatabaseSeeder.php
│   └── database.sqlite (gitignored)
├── public/{index.php,.htaccess,favicon.ico,robots.txt}
├── resources/
│   ├── views/{layouts/app,home,contact,partials/*}.blade.php
│   └── css/app.css  js/app.js         # vanilla; Vite é opcional
├── routes/{web,api,console}.php
├── storage/{app,framework/{cache,sessions,views},logs}/.gitkeep
├── tests/{Pest.php,TestCase.php,Feature/HomeTest.php}
├── .env.example  .gitignore  .editorconfig
├── composer.json  phpunit.xml  phpstan.neon  pint.json
├── slendie                            # CLI (php slendie ...)
├── AGENTS.md                          # convenções para agentes de IA
└── README.md
```

## 4. Ciclo de vida de um pedido

```
public/index.php
  → require vendor/autoload.php
  → $app = require bootstrap/app.php            (Application com basePath)
  → $request = Request::capture()
  → $response = $app->handle($request)
        Application::bootstrap(): LoadEnvironment → LoadConfiguration → HandleExceptions → RegisterProviders → BootProviders
        Kernel::handle(): Pipeline(global middleware) → Router::dispatch()
            Router: match (método + path, HEAD→GET, _method spoofing já aplicado no Request)
                    → Pipeline(route middleware) → Container::call(controller@action, routeParams + DI)
                    → normaliza retorno (string→Response, array→JsonResponse, View→ViewResponse, Response→Response)
        Handler::render() em caso de Throwable
  → $response->send()
  → $app->terminate($request, $response)
```

Nenhuma classe do core faz `echo`, `header()` ou `exit` fora de `Response::send()` e do comando `serve`.

## 5. Contratos públicos (API que os utilizadores vêem)

### Rotas (`routes/web.php`)
```php
use Slendie\Routing\Route;
use App\Http\Controllers\{HomeController, ContactController, Admin\LeadController};

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/sobre', 'about');
Route::post('/contacto', [ContactController::class, 'store'])->name('contact.store');

Route::prefix('/admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [LeadController::class, 'show'])->whereNumber('lead');
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);
});

Route::resource('/posts', PostController::class);          // index/create/store/show/edit/update/destroy
Route::redirect('/antigo', '/novo', 301);
Route::fallback(fn () => view('errors.404'));
```
Métodos aceites: GET, POST, PUT, PATCH, DELETE, OPTIONS; HEAD responde como GET sem body. `any()` e `match([...])`.
Rotas em `routes/api.php` recebem prefixo `/api` e grupo de middleware `api` (sem sessão/CSRF, com CORS).

### Controllers
```php
final class LeadController extends Controller
{
    public function __construct(private readonly DatabaseManager $db) {}       // DI pelo container

    public function index(Request $request): ViewResponse
    {
        $leads = Lead::whereLike('name', $request->query('q', ''))->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.leads.index', compact('leads'));
    }

    public function show(Lead $lead): array                                      // route-model binding → 404 automático
    {
        return $lead->toArray();                                                 // array → JSON
    }
}
```

### Views (Blade subconjunto, compilado)
Directivas: `@extends @section @endsection @yield @parent @include @includeIf @includeWhen @if @elseif @else @endif
@unless @endunless @isset @endisset @empty @endempty @foreach @endforeach @forelse @empty @endforelse @for @endfor
@while @endwhile @break @continue @php @endphp @csrf @method @error @enderror @vite @json @verbatim @stack @push @endpush
@once @endonce @auth @guest` (as duas últimas registadas pelo package auth). Echo: `{{ }}` escapado, `{!! !!}` cru,
`{{-- --}}` comentário. Variável `$loop` em foreach. `View::directive('nome', fn($expr) => ...)` para extensões.
Qualquer directiva desconhecida lança `CompileException` com ficheiro e linha.

```php
return view('home', ['clientes' => $clientes, 'title' => 'Lista de clientes']);
// ou, sem helper:
$html = $app->make(Factory::class)->render('home', [...]);
```

### Base de dados e Model
```php
$db->query('SELECT * FROM leads WHERE email = ?', [$email])->fetch();
$db->query('SELECT * FROM leads')->fetchAll();
$db->table('leads')->where('status', 'new')->count();

final class Lead extends Model
{
    protected string $table = 'leads';                      // opcional; default: snake plural do nome da classe
    protected array $fillable = ['name', 'email', 'message'];
    protected array $casts = ['created_at' => 'datetime', 'meta' => 'json'];
}

Lead::where('email', $email)->fetch();                      // === ->first()
Lead::whereLike('name', 'SILVA')->fetchAll();               // === ->get()  → Collection<Lead>
Lead::find(3);  Lead::findOrFail(3);  Lead::all();
Lead::create([...]);  $lead->update([...]);  $lead->delete();  $lead->save();
$lead->user;                                                // belongsTo lazy
```
Sinónimos oficiais: `fetch()` = `first()`, `fetchAll()` = `get()`. Sempre prepared statements.

### Migrações
```php
return new class extends Migration {
    public function up(): void {
        Schema::create('leads', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email')->index(); $t->text('message')->nullable();
            $t->string('status', 20)->default('new'); $t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('leads'); }
};
```
`php slendie migrate | migrate:rollback [--step=1] | migrate:status | migrate:fresh [--seed]`. SQL cru via `$this->db->statement()`.

### Middleware
```php
final class EnsureIsAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->get('is_admin')) return redirect('/login');
        return $next($request);
    }
}
// registo: app/Http/Kernel.php  →  'admin' => EnsureIsAdmin::class ; ou $router->aliasMiddleware('admin', ...) num provider
```

### Service Provider (framework, app e packages usam o mesmo)
```php
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void { $this->app->singleton(AuthManager::class); }
    public function boot(): void {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'auth');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->router->aliasMiddleware('auth', Authenticate::class);
        $this->blade->directive('auth', fn () => '<?php if (auth()->check()): ?>');
        $this->commands([InstallCommand::class]);
        $this->publishes([__DIR__.'/../config/auth.php' => config_path('auth.php')], 'auth-config');
    }
}
```
Descoberta automática: `composer.json` do package com `"extra": {"slendie": {"providers": ["Slendie\\Auth\\AuthServiceProvider"]}}`.

### CLI
`php slendie serve | migrate* | db:seed | make:{controller,model,migration,middleware,provider,command} | route:list |
view:clear | key:generate | vendor:publish --tag= | ai:context [routes|schema|info|all] [--json]`.

### Testes
```php
it('shows home', function () { $this->get('/')->assertOk()->assertSee('Bem-vindo'); });
it('stores lead', function () { $this->post('/contacto', [...])->assertRedirect('/'); expect(Lead::count())->toBe(1); })->uses(RefreshDatabase::class);
```

## 6. Decisões técnicas transversais

- **PHP 8.5**: `declare(strict_types=1)` em todos os ficheiros; tipos em propriedades, parâmetros e retornos; `readonly` em value objects; enums (`HttpMethod`, `ColumnType`); property hooks apenas onde simplifiquem (e.g. `Model` attributes). Sem `mixed` gratuito.
- **Sem estado estático** excepto Facades (`Route`, `Schema`, `View`, `DB`), que são açúcar sobre `app()->make()`. Facades resolvem pelo container e são reinicializáveis (`Facade::clearResolvedInstances()`) nos testes.
- **Erros**: `Handler` devolve página de debug (stack trace, request, env sanitizado) quando `APP_DEBUG=true`; em produção, views `errors/404` e `errors/500`; `expectsJson()` → JSON `{message, errors?}`. Tudo logado em `storage/logs/slendie.log` (um `Logger` mínimo PSR-3-like, sem dependência).
- **Segurança por defeito**: CSRF em grupo `web`; escaping em `{{ }}`; headers `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`; cookies de sessão `HttpOnly`, `SameSite=Lax`, `Secure` em HTTPS; `APP_KEY` para assinatura de cookies/tokens; prepared statements sempre; identificadores SQL (tabelas/colunas) validados por regex `^[A-Za-z_][A-Za-z0-9_\.]*$` e citados pela grammar.
- **Drivers de BD**: SQLite (default, ficheiro `database/database.sqlite`), MySQL/MariaDB, PostgreSQL. Grammars só divergem em: quoting de identificadores, `LIMIT/OFFSET`, DDL (tipos, auto-increment), `lastInsertId` (pgsql usa `RETURNING id`).
- **Sessão**: PHP nativa com `session_set_save_handler` para `storage/framework/sessions`; interface pequena para permitir driver alternativo via package.
- **Configuração**: `config/*.php` devolvem arrays; `config('app.name')`; `.env` lido por `Env` próprio (suporta `"quotes"`, `#` comentários, `${VAR}` interpolação simples). Cache de config não é objectivo da v4.0.
- **Qualidade**: PHPStan nível 8 na framework, Pint preset PSR-12, Pest com cobertura mínima 80% no core. CI: PHP 8.5, SQLite + MySQL (service).
- **Nomes**: pastas e classes em inglês (como o Laravel); documentação, mensagens de commit e comentários em pt-PT.

## 7. Fora de âmbito da v4.0 (candidatos a packages ou v4.x)
Queue, eventos/listeners (apenas `Application::booted()` callbacks), cache multi-driver, filesystem abstraction, mail
(package `slendie/mail` com PHPMailer), localização (`__()` fica como passthrough), rate limiting, policies/gates
(package auth pode trazer `can()` simples), Blade components (`<x-...>`), eager loading `with()`, many-to-many.

## 8. Mapa de implementação
Ver `core-03-backlog.md`. Cada peça `core-04-NN-*.md` é auto-contida, declara dependências, ficheiros a criar,
testes a passar e critérios de aceitação, e foi dimensionada para uma sessão de implementação por IA.
