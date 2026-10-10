# core-04-01 — Basement

> Peça 01 · Depende de: nada · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §2, §3, §6

## Objectivo
Criar o monorepo e o alicerce da framework: Container com autowiring, Application, leitura de `.env` e `config/`,
ServiceProvider, helpers base e tooling de qualidade. No fim, um script PHP consegue criar uma `Application`,
registar um provider, resolver um serviço com dependências e ler `config('app.name')`.

## Ficheiros a criar

### Raiz do monorepo
- `composer.json` (dev-only, `"name": "slendie/monorepo"`, `"type": "project"`, `minimum-stability: dev`, `prefer-stable: true`)
  - `repositories`: `{"type":"path","url":"framework"}`, `{"type":"path","url":"skeleton"}`, `{"type":"path","url":"packages/*"}`
  - `require`: `slendie/framework: @dev`
  - `require-dev`: `pestphp/pest ^4.0`, `phpstan/phpstan ^2.1`, `laravel/pint ^1.26`
  - `scripts`: `test` → `pest`, `analyse` → `phpstan analyse`, `lint` → `pint --test`, `fix` → `pint`
- `phpunit.xml` (testsuites: `framework/tests`, `packages/*/tests`), `phpstan.neon` (level 8, paths `framework/src`, `packages/*/src`), `pint.json` (preset psr12)
- `.gitignore`, `.editorconfig`, `.github/workflows/ci.yml` (PHP 8.5, composer install, test, analyse, lint; service MySQL 8 para a peça 04+)
- `README.md` do monorepo (o que é, estrutura, como desenvolver, link para docs/ e specs/)

### `framework/`
- `composer.json`:
  ```json
  {
    "name": "slendie/framework", "description": "Slendie Framework — um Laravel light para pequenos projectos PHP.",
    "type": "library", "license": "MIT", "keywords": ["framework","mvc","php","blade","micro-framework"],
    "require": {"php": "^8.5", "ext-pdo": "*", "ext-json": "*", "ext-mbstring": "*"},
    "autoload": {"psr-4": {"Slendie\\": "src/"}, "files": ["src/Support/helpers.php"]},
    "autoload-dev": {"psr-4": {"Slendie\\Tests\\": "tests/"}},
    "extra": {"branch-alias": {"dev-main": "4.x-dev"}}
  }
  ```
- `src/Container/Container.php`
  - `bind(string $abstract, Closure|string|null $concrete = null, bool $shared = false)`, `singleton()`, `instance()`, `alias()`
  - `make(string $abstract, array $parameters = []): mixed`, `has()`, `bound()`, `resolved()`
  - `call(callable|array|string $callback, array $parameters = []): mixed` — resolve dependências de um método/closure por tipo e por nome (parâmetros nomeados em `$parameters` têm prioridade; depois tipo via container; depois default; senão `BindingResolutionException`)
  - Autowiring por `ReflectionClass`; classes concretas resolvem sem bind; interfaces exigem bind
  - Detecção de dependência circular (stack de builds)
  - `extend(string $abstract, Closure $closure)` para decorar
  - `static getInstance()/setInstance()` (necessário para helpers e facades)
- `src/Container/BindingResolutionException.php`, `src/Container/CircularDependencyException.php`
- `src/Foundation/Application.php extends Container`
  - `__construct(string $basePath)`; `const VERSION = '4.0.0-dev'`
  - paths: `basePath()`, `appPath()`, `configPath()`, `databasePath()`, `publicPath()`, `resourcePath()`, `storagePath()`, `bootstrapPath()`; todos aceitam sufixo opcional
  - `environment(): string` (`APP_ENV`, default `production`), `isDebug()`, `isProduction()`, `runningInConsole()`, `runningUnitTests()`
  - `bootstrapWith(array $bootstrappers)`; `hasBeenBootstrapped()`
  - `register(ServiceProvider|string $provider)`, `boot()`, `booted(Closure)`, `isBooted()`, `getProviders()`
  - regista-se a si própria como `Application::class`, `Container::class`, `'app'`
  - `handle(Request)` e `terminate()` ficam `// TODO(core-04-02)`
- `src/Foundation/Bootstrap/LoadEnvironment.php` — lê `{basePath}/.env` (ou `.env.{APP_ENV}` se existir) via `Env::load`; não falha se não existir
- `src/Foundation/Bootstrap/LoadConfiguration.php` — carrega todos os `config/*.php` para `Config` e regista como singleton `Config::class` e `'config'`; define `date_default_timezone_set(config('app.timezone','UTC'))`
- `src/Foundation/Bootstrap/RegisterProviders.php` — lê `config('app.providers', [])` + providers da framework (lista interna, vazia nesta peça) e chama `register()`
- `src/Foundation/Bootstrap/BootProviders.php` — chama `boot()` em todos, com DI via `Container::call`
- `src/Foundation/Bootstrap/HandleExceptions.php` — `error_reporting(E_ALL)`, converte erros em `ErrorException`, `set_exception_handler` que delega para `Exceptions\Handler` (nesta peça: imprime mensagem em CLI; página HTML mínima no web — a página de debug completa é da peça 02)
- `src/Foundation/ServiceProvider.php` (abstract) — `__construct(protected Application $app)`, `register(): void {}`, `boot(): void {}`; helpers `loadRoutesFrom/loadViewsFrom/loadMigrationsFrom/publishes/commands/mergeConfigFrom` existem mas lançam `LogicException('disponível na peça 09')` excepto `mergeConfigFrom` (implementar já)
- `src/Support/Env.php` — `load(string $path)`, `get(string $key, mixed $default = null)`, `set()`, `has()`, `all()`; parse: ignora linhas vazias e `#`; `KEY=value`, `KEY="com espaços"`, `KEY='literal'`, `export KEY=`, interpolação `${OTHER}`; converte `true/false/null/empty` (case-insensitive) nos valores sem aspas; escreve em `$_ENV` e `putenv`
- `src/Support/Config.php` — `get('app.name', default)` com dot notation, `set()`, `has()`, `all()`, `ArrayAccess`
- `src/Support/Arr.php` — `get/set/has/forget` dot notation, `only/except`, `first/last`, `flatten`, `pluck`, `wrap`, `isAssoc`
- `src/Support/Str.php` — `snake/camel/studly/kebab`, `plural/singular` (regras inglesas simples + lista de irregulares; suficiente para nomes de tabelas), `slug`, `random(int)`, `startsWith/endsWith/contains`, `limit`, `after/before`, `lower/upper/title`, `uuid()`
- `src/Support/Facade.php` (abstract) — `__callStatic` resolve `getFacadeAccessor()` no container; `clearResolvedInstances()`, `setFacadeApplication()`
- `src/Support/helpers.php` — todas com `if (! function_exists())`: `app(?string $abstract = null, array $params = [])`, `env()`, `config(string|array|null $key = null, $default = null)` (array → set), `base_path()`, `app_path()`, `config_path()`, `database_path()`, `public_path()`, `resource_path()`, `storage_path()`, `value()`, `tap()`, `e(string): string` (htmlspecialchars ENT_QUOTES UTF-8), `dd(...)`, `dump(...)`, `class_basename()`, `str()`/`arr()` passthrough opcionais. Os helpers HTTP/view ficam para as peças 02/03.
- `src/Exceptions/SlendieException.php` (base de todas as excepções da framework)
- `tests/Pest.php`, `tests/Unit/Container/ContainerTest.php`, `tests/Unit/Support/{EnvTest,ConfigTest,ArrTest,StrTest}.php`, `tests/Unit/Foundation/ApplicationTest.php`, `tests/fixtures/app/` (basePath de teste com `.env` e `config/app.php`)

## Comportamentos a testar (Pest)
- Container: bind closure; singleton devolve a mesma instância; autowiring de classe com dependências aninhadas; interface sem bind lança `BindingResolutionException`; `call()` injecta por tipo e aceita parâmetros nomeados; dependência circular lança `CircularDependencyException`; `extend` decora.
- Env: todos os formatos de linha acima; `${VAR}` interpola; `.env` inexistente não lança.
- Config: dot notation get/set/has; default; `config(['a.b' => 1])` escreve.
- Str: `plural('lead') === 'leads'`, `plural('category') === 'categories'`, `plural('person') === 'people'`, `snake('LeadController') === 'lead_controller'`, `studly('contact_form') === 'ContactForm'`.
- Application: paths correctos; `bootstrapWith` carrega env e config da fixture; `register()` + `boot()` chamam o provider pela ordem certa; `booted()` callback corre após boot; `environment()` lê `APP_ENV`.

## Critérios de aceitação
- `composer install` na raiz instala `slendie/framework` por path.
- `composer test`, `composer analyse`, `composer lint` verdes.
- Script de fumo (`framework/tests/smoke.php`, não é teste Pest):
  ```php
  $app = new Application(__DIR__.'/fixtures/app');
  $app->bootstrapWith([LoadEnvironment::class, LoadConfiguration::class, RegisterProviders::class, BootProviders::class]);
  echo config('app.name');            // "Fixture"
  echo $app->make(Some\Service::class)::class;
  ```
- Nenhum ficheiro sem `declare(strict_types=1)`; nenhuma função global fora de `helpers.php`.

## Não fazer nesta peça
Request/Response/Router, Blade, Database, Console. `Application::handle()` fica como TODO.

## Desvios
Implementado em 2026-10-10. Divergências face ao texto acima:

1. **`ServiceProvider::boot()` não está declarado na classe base.** Declará-lo impediria as subclasses de pedirem
   dependências (`boot(Config $config)`), porque o PHP exige assinaturas compatíveis. A `Application` chama `boot()` via
   `Container::call()` apenas se o método existir. Só `register(): void` está declarado.
2. **Pasta de dados de fixtures é `framework/tests/_data/`**, não `fixtures/`: em Windows (case-insensitive) colidia com
   `Fixtures/` (PSR-4 `Slendie\Tests\Fixtures`). Uma classe por ficheiro em `Fixtures/`.
3. **Existe `tests/Pest.php` na raiz do monorepo** porque o Pest exige a pasta `tests/`; esse ficheiro aplica o
   `beforeEach` global a `framework/tests` e `packages/*/tests`. O `framework/tests/Pest.php` mantém-se para testar o
   package isolado.
4. `Container::getInstance()` devolve `Container` (não `static`) e usa `new self()` — `Application` precisa de
   `basePath` e não pode ser criada implicitamente.
5. Callbacks `resolving()` registados por **tipo** (interface) disparam uma vez por resolução mesmo quando um binding
   delega para outro (`bind('greeter', Greeter::class)`).
6. `Handler` mínimo já existe (`report()` para `storage/logs/slendie.log`, `renderForConsole()`, `renderRaw()`) para o
   bootstrapper `HandleExceptions` ter onde delegar; a peça 02 substitui `renderRaw()` por `render(Request): Response`.
7. Helpers extra incluídos por serem triviais e úteis já: `blank()`, `filled()`, `retry()`, `dump()`.
8. `Str::plural()` aceita `$count` como segundo argumento (devolve singular para 1), como no Laravel.
