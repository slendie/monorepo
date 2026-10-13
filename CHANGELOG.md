# Changelog

Todas as alterações relevantes são registadas aqui. Formato baseado em [Keep a Changelog](https://keepachangelog.com/pt/1.1.0/);
versionamento [SemVer](https://semver.org/lang/pt-BR/).

## [Unreleased]

### Added
- Análise crítica, arquitectura, backlog e 13 especificações de implementação em `specs/`.
- Documentação completa da API planeada em `docs/`.
- Peça 01 — basement: monorepo, `Container` com autowiring, `Application`, `Env`, `Config`, `Arr`, `Str`,
  `Facade`, `ServiceProvider`, bootstrappers e helpers base, com testes Pest, PHPStan 8 e Pint.
- Peça 02 — HTTP e routing: `Request`/`Response` (JSON, redirect, view lazy, cookies, uploads), `Router` com grupos, resources,
  binding e fallback, `Pipeline`, `Kernel`, sessão por ficheiro, middlewares (sessão, CSRF, trim, headers de segurança, CORS),
  `Handler` com páginas 404/405/419/500 e página de debug, `UrlGenerator`, facades `Route`/`URL`/`Response`/`Session`,
  helpers HTTP e skeleton mínimo (`public/index.php`, `bootstrap/app.php`).
- Peça 03 — views e Blade compilado: `View\Factory`, `Finder` (paths, namespaces `pkg::view`), `View`, `Engine` e `BladeCompiler`
  (`.blade.php` → PHP em `storage/framework/views`, cache por mtime e, em debug, por hash do conteúdo; PHP compilado com as
  mesmas linhas do template, por isso os erros apontam para a linha original), directivas de layouts, condicionais, ciclos com
  `$loop`, formulários, `@json`, `@class`, `@once`, stacks e directivas personalizadas, `CompileException` com linha para
  directivas desconhecidas, `ViewException`/`ViewNotFoundException`, `$errors`, helper `view()`, facades
  `View`/`Blade`, páginas de erro e de debug convertidas para Blade (`errors::`, com override pelo projecto) e skeleton com
  `layouts/app` + `home` em `/`.
- Peça 04 — base de dados: `DatabaseManager` (ligações nomeadas preguiçosas, `extend()`), `Connection` (PDO preguiçoso, prepared statements,
  transacções com savepoints, query log, `listen()`, `pretend()`), `ConnectionFactory` (SQLite, MySQL/MariaDB, PostgreSQL), `Statement`,
  Query Builder fluente com grammars por driver (identificadores e operadores validados), `Expression`, `JoinClause`, paginação
  (`Paginator`, `SimplePaginator`, views `pagination::default|simple`), `QueryException` com dica de migração, `RecordNotFoundException`
  (404 no `Handler`), facade `DB` e `config/database.php`.
- Peça 05 — models: `Model` (atributos, casts, accessors/mutators, mass-assignment, dirty tracking, timestamps, eventos por classe,
  `save/update/delete/refresh/fresh/replicate`, estáticos `create/find/findOrFail/all/destroy/…` e proxies para o Query Builder com
  `fetch()/fetchAll()`), `Model\Builder` (scopes locais e globais, `paginate`, `chunk`, `cursor`, `firstOrCreate`, `updateOrCreate`),
  `Collection`, relações lazy `hasOne/hasMany/belongsTo`, traits `SoftDeletes` e `HasUuids`, `ModelNotFoundException` (404) e
  route-model binding completo (`{lead}` e `{post:slug}`).
- Peça 06 — schema, migrações e seeders: `Schema\Builder` (`create/table/drop/rename`, `hasTable/hasColumn`, `getColumns`, `getTables`,
  `dropAllTables`, `withoutForeignKeyConstraints`), `Blueprint` com todos os tipos de coluna, modifiers, índices e chaves estrangeiras,
  grammars de DDL para SQLite, MySQL/MariaDB e PostgreSQL (identificadores validados, literais citados), facade `Schema`
  (`Schema::connection()`), `Migration`, `MigrationRepository` (batches), `Migrator` (`run/rollback/reset/fresh/status/pretend`, transacção
  por migração onde o driver o permite, várias pastas, classes anónimas e nomeadas), `MigrationCreator` com stubs, `Seeder`
  (`call/callWith`, injecção de dependências), migração `create_leads_table` e seeders de exemplo no skeleton, `docs/migrations.md`
  e `docs/seeding.md`.
- Peça 07 — consola (`php slendie`), sem dependências: `Console\Application` (registo, `find` com sugestões, `run`, `call`), `Command`
  (assinatura estilo Laravel com descrições, `handle()` com DI, `ask/secret/confirm/choice`, `table`, `task`, `call/callSilently`,
  verbosidade e cores ANSI que respeitam `NO_COLOR`), `Input`/`Output`/`BufferedOutput`, `SignatureParser`, comandos por closure
  (`routes/console.php`, facade `Console`), descoberta de `app/Console/Commands`, `ServiceProvider::commands()` e
  `GeneratorCommand` com stubs (os de `stubs/` do projecto têm prioridade). Comandos: `serve`, `migrate`, `migrate:install|status|rollback|reset|refresh|fresh`,
  `db:seed`, `db:wipe`, `make:controller|model|migration|middleware|provider|command|seeder|view`, `route:list`, `view:clear`,
  `key:generate`, `vendor:publish`, `about`, `env`, `list` e `help`. Script `skeleton/slendie`, `routes/console.php` e
  `bootstrap/providers.php` no skeleton; `docs/cli.md` e secção "Registar comandos" em `docs/packages.md`.
- Peça 08 — validação e traduções: `Validator` com ~100 regras estilo Laravel (strings, arrays, objectos `Rule`, closures, wildcards
  `items.*.qty`, `bail`, `sometimes`, `nullable`, `exclude*`, `after`, `stopOnFirstFailure`), `Rule::in/unique/exists/enum/requiredIf/when`,
  `Password`, `Rule::file()`, `Factory` (`extend`) e facade `Validator`, `MessageBag`, `ValidatedInput` (`safe()`), `ValidationException`
  (422 JSON ou redirect de volta com erros e `old()`), `FormRequest` injectável (`authorize`, `prepareForValidation`, `after`),
  `Request::validate()/validator()`, `Translator` (fallback de locale, override pelo projecto, `choice`, namespaces de packages),
  textos pt-PT e en, helpers `__`/`trans`/`trans_choice`/`validator`, `make:request`, `vendor:publish --tag=slendie-lang`,
  `ContactController` no skeleton, `docs/validation.md` e `docs/localization.md`.
- Peça 09 — packages, Vite, logging e `ai:context`: `PackageDiscovery` (`vendor/composer/installed.json`, `extra.slendie.providers|aliases`,
  `dont-discover` por nome ou `*`, cache em `bootstrap/cache/packages.php` via `package:discover`), `ServiceProvider` completo
  (`loadRoutesFrom`, `loadViewsFrom` com override em `resources/views/vendor/{ns}`, `loadMigrationsFrom`, `loadTranslationsFrom` com
  override em `lang/vendor/{ns}`, `publishesMigrations`, `router()`, `blade()`), `Application::registerConfiguredProviders()` e
  `addDeferredServices()`, `Blade::if()`, `Support\Vite` (hot/build, preload, nonce, `asset`, `content`, `reactRefresh`) com
  `@vite`/`@viteReactRefresh`/`vite()`, `Logger` (PSR-3 sem dependência; canais `single|daily|stderr|null|stack`, `extend`, níveis mínimos,
  gc dos logs diários), facade `Log`, helpers `logger()`/`report()`, `config/logging.php`, comandos `package:discover` e `ai:context`
  (`--json`, config sanitizada), `about` lista os packages, `VendorPublishCommand` renomeia migrações publicadas; skeleton sem Node
  (`public/css/app.css`, `public/js/app.js`, layout com `@vite` condicional, `config/logging.php`, `bootstrap/cache/.gitignore`);
  `docs/logging.md`, e actualizações em `docs/packages.md`, `docs/vite.md` e `docs/ai-agents.md`.
- Peça 10 — testing: `Slendie\Testing\TestCase` (aplicação nova por teste em `APP_ENV=testing`, sessão em memória, CSRF desligado,
  limpeza total no fim), pedidos HTTP sintéticos (`get/post/put/patch/delete/options/head`, variantes `*Json`, `call()`, cabeçalhos,
  token, cookies, `withSession`, `from`, `followingRedirects`, `withoutMiddleware`, `withoutExceptionHandling`, uploads),
  `TestResponse` com asserções de estado, redirects, cabeçalhos, cookies, corpo, views, JSON e sessão/erros de validação (com a
  excepção e o excerto do corpo nas mensagens de falha), asserções de base de dados (`assertDatabaseHas/Missing/Count`,
  `assertModelExists`, `assertSoftDeleted`, `seed`), traits `RefreshDatabase` (migra uma vez por processo + transacção por teste,
  também em SQLite em memória), `DatabaseTransactions`, `DatabaseMigrations` e `WithoutMiddleware`, `console()` com
  `PendingCommand` (`expectsOutput`, `expectsTable`, `expectsQuestion`, `expectsConfirmation`, `expectsChoice`),
  `swap()/instance()`, ganchos de `actingAs()` para o package auth e `FakeUploadedFile`. Skeleton com `phpunit.xml`, `tests/Pest.php`,
  `tests/TestCase.php` e testes de `Home` e `Contact`. Cobertura do core a 93 % (mínimo 80 %), PHPStan nível 8 sem baseline nem
  `ignoreErrors`, Pint limpo, benchmark informativo em `framework/tests/bench` e `docs/performance.md`.
- Peça 11 — skeleton `slendie/slendie`: landing page (hero, benefícios, formulário) com CSS/JS próprios (sem Node, dark mode, envio por
  `fetch` com fallback), `StoreLeadRequest` (honeypot `website`, consentimento RGPD), `Lead` (scopes `status`/`new`/`search`, `initials`,
  `Lead::filtered()`), `ContactController` (redirect para `/obrigado` ou 201 JSON), páginas `obrigado`/`privacidade`/`termos`, 404/500 com o
  layout do site, API `/api/leads` (index/show/update/destroy) protegida por `VerifyApiToken` (Bearer, 503 se `API_TOKEN` vazio),
  `AppServiceProvider`, `config/view.php` e `seo.*`, `post-create-project-cmd` portável (cria o SQLite, `key:generate`, `migrate`), testes
  Pest (Home, Contact, Api/Lead), `phpstan.neon` nível 6, `pint.json`, `AGENTS.md`, `README`, `LICENSE`. Framework: comando
  `ai:agents-file [--force]` e `stubs/AGENTS.md.stub`. Monorepo: `tests/Skeleton/CreateProjectTest.php` (grupo `slow`, `composer test:slow`).
- Peça 12 — package `slendie/auth`: `AuthManager` (`guard()`, `extend()`, atalhos `user/check/guest/id/attempt/once/login/logout/viaRemember`),
  `SessionGuard` (login com regeneração da sessão e do token CSRF, remember-me por cookie `id|token|hmac` assinada com a `APP_KEY` e com só o
  sha256 na base de dados, logout que roda o token) e `TokenGuard` (Bearer → sha256 em `personal_access_tokens`, expiração, abilities,
  `last_used_at` com intervalo de 1 minuto), `ModelUserProvider` (model configurável, fallback para o `User` do package, rehash automático),
  traits `Authenticatable` e `HasApiTokens`, `Hasher` (bcrypt ou argon2id), middlewares `auth`, `guest` e `ability`, directivas
  `@auth/@guest`, `redirect()->intended()`, `LoginController`/`LogoutController` com limitador de tentativas em ficheiros (429 + `Retry-After`),
  views e traduções pt/en publicáveis, migrações `users` e `personal_access_tokens`, comandos `auth:install`, `make:user` e `auth:token`,
  helpers `auth()` e `bcrypt()`. `docs/auth.md` e `packages/auth/README.md`; 89 testes Pest.

### Changed
- `Application::VERSION` passa a `4.0.0`; `Handler::report()` delega no `Logger` (linha com milissegundos e a excepção por extenso).
- `Router::toResponse()` renderiza as views dentro do pipeline (excepções e `abort()` em views passam pelo `Handler`; `old()`/flash
  funcionam durante o render).
- `Contracts\ViewFactory` ganha `exists()`; `ShareErrorsFromSession` partilha `$errors` como `Validation\MessageBag` (o `ViewErrorBag` desaparece).
- `Request::validate()` devolve os dados validados; `RedirectResponse::withErrors()` aceita `MessageBag`, `Validator` ou array.
- `Migrator::setOutput()` aceita `null` (deixa de emitir mensagens); `Foundation\Application` regista `Console\Application` como
  singleton e a `ConsoleServiceProvider` entre os providers da framework.
