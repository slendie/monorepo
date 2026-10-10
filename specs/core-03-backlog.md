# Slendie v4 — Backlog de implementação

> Cada linha é uma peça implementável numa única sessão de IA, com a sua spec em `core-04-NN-*.md`.
> Ordem = dependências. Estado: ⬜ por fazer · 🟨 em curso · ✅ feito.

## Fase 1 — Núcleo (framework/)

| # | Peça | Spec | Entrega | Depende de | Estado |
|---|---|---|---|---|---|
| 01 | Basement: monorepo, composer, Container, Application, Env, Config, ServiceProvider, helpers base, tooling (Pest/PHPStan/Pint/CI) | `core-04-01-basement.md` | `php -r` cria `Application`, resolve bindings; `composer test` verde | — | ✅ 2026-10-10 (64 testes, PHPStan 8, Pint) |
| 02 | HTTP + Routing: Request, Response*, Session, Router, Route facade, Pipeline, Kernel, middlewares base (CSRF, StartSession, SecurityHeaders, Cors, TrimStrings), Controller, Exceptions/Handler, `public/index.php` | `core-04-02-http-routing.md` | pedido → resposta com rotas, grupos, params, spoofing, 404/405, debug page | 01 | ⬜ |
| 03 | Views: Factory, BladeCompiler, directivas, cache, `view()`, páginas de erro | `core-04-03-views-blade.md` | `view('home', [...])` com layouts e todas as directivas listadas | 01, 02 (ViewResponse) | ⬜ |
| 04 | Database: DatabaseManager, Connection (3 drivers), Query Builder, Grammars, `query()->fetch()/fetchAll()`, paginate | `core-04-04-database-query.md` | builder gera SQL válido nos 3 drivers; testes em SQLite memória | 01 | ⬜ |
| 05 | Model: Model base, Model\Builder, Collection, relações HasOne/HasMany/BelongsTo, route-model binding | `core-04-05-model.md` | `Lead::whereLike()->fetchAll()`, `save()`, casts, timestamps, binding em rotas | 02, 04 | ⬜ |
| 06 | Schema + Migrations + Seeders: Schema facade, Blueprint, DDL grammars, Migration, Migrator (batches, rollback real), Seeder | `core-04-06-schema-migrations.md` | `up()/down()` reais nos 3 drivers; repositório `migrations` | 04 | ⬜ |
| 07 | Console: Application, Command, Input/Output, comandos `serve`, `migrate*`, `db:seed`, `make:*`, `route:list`, `view:clear`, `key:generate`, `vendor:publish`; stubs | `core-04-07-console.md` | `php slendie <cmd>` funcional, comandos registáveis por providers | 01–06 | ⬜ |
| 08 | Validation + flash: Validator, regras, MessageBag, `$request->validate()`, `old()`, `@error`, redirect back com erros | `core-04-08-validation.md` | formulário de contacto valida e re-popula | 02, 03 | ⬜ |
| 09 | Packages & assets: PackageDiscovery, `publishes`, `loadRoutesFrom/ViewsFrom/MigrationsFrom`, Vite helper + `@vite`, Logger, `ai:context` | `core-04-09-packages-vite.md` | package externo regista rotas/views/migrações/directivas/comandos só com `extra.slendie.providers` | 01–07 | ⬜ |
| 10 | Testing: TestCase, TestResponse, RefreshDatabase, asserts; cobertura ≥ 80% do core | `core-04-10-testing.md` | `$this->get('/')->assertOk()` em projectos criados a partir do skeleton | 01–09 | ⬜ |

## Fase 2 — Skeleton e packages

| # | Peça | Spec | Entrega | Depende de | Estado |
|---|---|---|---|---|---|
| 11 | Skeleton `slendie/slendie`: estrutura completa, landing page + formulário de contacto + `Lead`, CLI `slendie`, `.env.example`, `bin/setup`, `AGENTS.md`, README | `core-04-11-skeleton.md` | `composer create-project` → `php slendie serve` → landing funcional e lead gravado | 01–10 | ⬜ |
| 12 | Package `slendie/auth`: AuthManager, SessionGuard, TokenGuard (API), model `User`, migrações, middlewares `auth`/`guest`, views login/logout, `@auth/@guest`, `auth:install`, `make:user` | `core-04-12-package-auth.md` | `composer require slendie/auth && php slendie auth:install` protege `/admin` | 09, 11 | ⬜ |
| 13 | Package `slendie/react-admin`: Vite + React + painel de leads (lista, filtro, detalhe, estado), consome `/api/leads`, `react-admin:install` | `core-04-13-package-react-admin.md` | `npm run dev` → painel em `/admin` autenticado | 11, 12 | ⬜ |

## Fase 3 — Publicação

| # | Peça | Entrega | Estado |
|---|---|---|---|
| 14 | Split do monorepo em 4 repositórios GitHub, submissão ao Packagist, webhooks, tags `v4.0.0` | `composer create-project slendie/slendie` funciona a partir do Packagist | ⬜ |
| 15 | Site de documentação a partir de `docs/` (slendie.lcloss.dev) | docs online | ⬜ |

## Regras para quem implementa uma peça

1. Ler `core-02-architecture.md` e a spec da peça por inteiro antes de escrever código.
2. Não antecipar peças seguintes: se um método "fica para a peça N", deixar `// TODO(core-04-N)` e nada mais.
3. Toda a peça termina com `composer test`, `composer analyse` (PHPStan 8) e `composer lint` verdes na raiz do monorepo.
4. Actualizar o Estado nesta tabela e, se a implementação divergir da spec, anotar a divergência no fim da própria spec (secção "Desvios").
5. Documentação de utilizador em `docs/` é actualizada na mesma peça que entrega a funcionalidade.
