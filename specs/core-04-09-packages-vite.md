# core-04-09 — Packages, descoberta, publicação, Vite, Logger e `ai:context`

> Peça 09 · Depende de: 01–07 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Service Provider), análise crítica §4.3

## Objectivo
Tornar a framework extensível: um package Composer com `extra.slendie.providers` é descoberto e o seu provider pode
registar rotas, views (com namespace), migrações, directivas Blade, middlewares, comandos, config e publicar ficheiros.
Também fecha pontas soltas do core: `Vite` + `@vite`, `Logger`, `ai:context`.

## Ficheiros a criar / completar (`framework/src/`)
- `Foundation/PackageDiscovery.php`
  - `discover(string $vendorPath): array{providers: string[], aliases: array, dont-discover: string[]}` — lê `vendor/composer/installed.json` (formato `packages` array, Composer 2) e, para cada package, `extra.slendie.providers` e `extra.slendie.aliases`; respeita `extra.slendie.dont-discover` do `composer.json` do **projecto** (lista de package names ou `*`)
  - cache opcional em `bootstrap/cache/packages.php` gerado por `package:discover` (comando novo) e corrido automaticamente por `post-autoload-dump` no skeleton; se cache não existir, descobre em runtime (custo: ler um JSON)
  - `RegisterProviders` passa a juntar: providers da framework + descobertos + `bootstrap/providers.php`
- `Foundation/ServiceProvider.php` — implementar de verdade:
  - `loadRoutesFrom(string $path, array $attributes = ['middleware' => 'web'])` — `Router::group($attributes, $path)` em `boot`
  - `loadViewsFrom(string|array $path, string $namespace)` — `View\Factory::addNamespace`; se existir `resource_path("views/vendor/{$namespace}")` no projecto, essa pasta tem prioridade (override de views do package)
  - `loadMigrationsFrom(string|array $paths)` — `Migrator::path()`
  - `loadTranslationsFrom(string $path, string $namespace)` — `Translator::addNamespace` (`__('auth::messages.failed')`); override em `lang/vendor/{namespace}`
  - `mergeConfigFrom(string $path, string $key)` — config do package com valores do projecto por cima (`array_replace_recursive`? **não**: merge raso de 1º nível como Laravel; documentar)
  - `publishes(array $paths, string|array $groups = null)` — acumula estático por provider; `vendor:publish` lê; `publishesMigrations(array $paths, $groups)` renomeia timestamps para "agora" ao publicar
  - `commands(array|string $commands)`
  - `callAfterResolving(string $abstract, Closure)` — regista callbacks de `resolving` (útil para registar directivas Blade só quando o compiler é criado)
  - helpers protegidos: `$this->app`, `$this->router()`, `$this->blade()`
  - `provides(): array` e **deferred providers**: `bool $defer = false` — se `true`, só `register` quando um `provides()` é pedido ao container (simples: `Application::registerDeferredProvider` por abstract)
  - `isDeferred()`, `when()` **não**
- `Foundation/Application.php` — `registerConfiguredProviders()` com descoberta; `getProvider(class)`, `providerIsLoaded()`; `addDeferredServices()`, `loadDeferredProvider()`; `Application::VERSION` final `4.0.0`
- `Support/Vite.php`
  - `__construct(string $publicPath, string $buildDirectory = 'build', string $hotFile = 'public/hot', array $entryPoints = [])`
  - `useBuildDirectory()`, `useHotFile()`, `useManifestFilename()`, `withEntryPoints()`, `useIntegrityKey()` **não**, `useCspNonce()` → `nonce` attributes
  - `isRunningHot(): bool` (existe `public/hot` com URL do dev server — estratégia do Laravel: o plugin `laravel-vite-plugin` escreve esse ficheiro; **usar `laravel-vite-plugin` no `react-admin`**, é compatível com qualquer backend que leia `public/hot` e `manifest.json`)
  - `__invoke(string|array $entrypoints, ?string $buildDirectory = null): string` → tags: em hot: `<script type="module" src="{hot}/@vite/client"></script>` + `<script type="module" src="{hot}/{entry}">` (CSS entries como `<link>`? em dev, CSS é importado pelo JS; se entry termina em `.css` emitir `<link rel="stylesheet" href="{hot}/{entry}">`); em build: lê `public/{build}/manifest.json`, emite `<link rel="stylesheet">` para `css` do chunk e dos `imports` recursivos, `<link rel="modulepreload">` para imports, `<script type="module">` para o `file`; manifest em falta → `ViteManifestNotFoundException` com instrução `npm run build`
  - `asset(string $path): string` (URL de um asset do manifest), `reactRefresh(): string` (preamble para React Fast Refresh em dev, como `@viteReactRefresh`), `content(path)` (inline), `manifestHash()`
  - directivas `@vite(...)` → `<?php echo app(Vite::class)(...); ?>` e `@viteReactRefresh` → `reactRefresh()`; registadas no `ViewServiceProvider` (substituir stub da peça 03); helper `vite(...)` opcional
- `Log/Logger.php` + `Log/LogServiceProvider.php` + facade `Log`
  - PSR-3-like sem a dependência (`emergency..debug(string|Stringable $message, array $context = [])`, `log(level, …)`); interpolação `{key}` do contexto; linha `[Y-m-d H:i:s.v] {env}.{LEVEL}: {message} {context_json}`; excepções no contexto (`['exception' => $e]`) imprimem classe, mensagem, ficheiro:linha e trace
  - canais: `single` (`storage/logs/slendie.log`), `daily` (`slendie-Y-m-d.log`, `days` para gc), `stderr`, `null`, `stack`(lista); `config/logging.php` (`default`, `channels`); `Log::channel('x')`; `Logger::extend(driver, Closure)`
  - `Handler::report()` usa `Log::error($e->getMessage(), ['exception' => $e])`
  - `report(Throwable)` helper, `logger(?message, context)` helper (sem args devolve Logger), `info()` **não**
- `Console/Commands/PackageDiscoverCommand` — `package:discover`: escreve `bootstrap/cache/packages.php` e lista "Discovered Package: slendie/auth"
- `Console/Commands/AiContextCommand` — `ai:context {section? : routes|schema|config|info|all} {--json}` — devolve para agentes de IA: rotas (como `route:list`), schema (tabelas e colunas via `Schema::getColumns`), info (versões, env, debug, driver, providers, packages descobertos, middlewares globais e aliases, directivas Blade custom, comandos disponíveis), config (sanitizada); `--json` emite um único objecto. Equivalente modernizado de `bin/ai-context.php` do skeleton v1.1
- `Console/Commands/VendorPublishCommand` (peça 07) — adaptar para `publishesMigrations`
- `Console/Commands/AboutCommand` — mostrar packages descobertos
- `Routing/Router::aliasMiddleware/middlewareGroup/pushMiddlewareToGroup` e `Http/Kernel::*` — garantir que providers conseguem alterar grupos (`$this->router()->pushMiddlewareToGroup('web', X::class)`)
- `View/Compiler/BladeCompiler::if(string $name, Closure)` — açúcar: `Blade::if('admin', fn () => ...)` cria `@admin/@elseadmin/@else/@endadmin` (necessário para `@auth/@guest` no package auth: `Blade::if('auth', fn (?string $guard = null) => auth($guard)->check())`)
- `Testing/`? não; `Foundation/Testing` fica para 10

### Skeleton
- `composer.json` do skeleton: `"scripts": {"post-autoload-dump": ["@php slendie package:discover --ansi"], "post-create-project-cmd": ["@php -r \"file_exists('.env') || copy('.env.example', '.env');\"", "@php slendie key:generate --ansi", "@php slendie migrate --force --ansi"]}`; `"extra": {"slendie": {"dont-discover": []}}`
- `bootstrap/cache/.gitignore` (`*` excepto `.gitignore`)
- `config/logging.php`
- `resources/views/layouts/app.blade.php` usa `@vite(['resources/css/app.css', 'resources/js/app.js'])` **só se** `file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot'))`; caso contrário `<link href="/css/app.css">` — **decisão**: o skeleton base **não** depende de Node. CSS/JS vanilla em `public/css/app.css` e `public/js/app.js`. `@vite` documentado para quem adicionar Vite (e usado pelo react-admin). Remover `resources/css|js` do skeleton base
- `package.json` **não** existe no skeleton base; o `react-admin:install` cria-o

## Package de teste (`framework/tests/fixtures/packages/acme-blog/`)
Um package fictício com `composer.json` (`extra.slendie.providers`), provider que: carrega rotas (`/acme`), views (`acme::index`), migrações, traduções, directiva `@acme`, `Blade::if('acme')`, middleware alias `acme`, comando `acme:hello`, `mergeConfigFrom`, `publishes` de config e views. O teste gera um `installed.json` falso apontando para esta pasta.

## Testes (Pest)
- `PackageDiscovery`: lê `installed.json` (fixture), respeita `dont-discover` (nome e `*`), cache escrito/lido, package sem `extra` ignorado.
- Provider do package de teste: cada capacidade acima verificada ponta a ponta (request a `/acme` renderiza `acme::index`; view override em `resources/views/vendor/acme/index.blade.php` vence; migração corre com `migrate`; `__('acme::m.hi')` e override em `lang/vendor/acme`; `@acme` compila; `@acme … @endacme` condicional; middleware alias aplica; `acme:hello` aparece em `list`; config merge: projecto vence; `vendor:publish --tag=acme-config` copia; `publishesMigrations` renomeia timestamp).
- Deferred provider só regista quando resolvido.
- `Vite`: hot (ficheiro `public/hot`) gera tags dev + `reactRefresh`; build gera tags de manifest fixture (css, imports, modulepreload); manifest em falta lança com mensagem; `asset()`; nonce.
- `Logger`: formato de linha; interpolação; excepção no contexto; canais `single/daily/stack/null`; `daily` gc; `Log::channel`.
- `ai:context`: cada secção; `--json` é JSON válido com chaves `routes, schema, config, info`; config sanitiza `*KEY*`/`*PASSWORD*`.
- `package:discover` escreve cache e `RegisterProviders` usa-a.

## Critérios de aceitação
- Um package externo mínimo (só `composer.json` + provider) é descoberto e activo após `composer require`, sem tocar em ficheiros do projecto.
- `composer test/analyse/lint` verdes; `docs/packages.md` (criar um package passo a passo, publicar no Packagist), `docs/vite.md`, `docs/logging.md`, `docs/ai-agents.md` (`ai:context`, `AGENTS.md`).

## Desvios
(preencher na implementação)
