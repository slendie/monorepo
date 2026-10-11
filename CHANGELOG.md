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
  directivas desconhecidas, `ViewException`/`ViewNotFoundException`, `ViewErrorBag` (`$errors`), helper `view()`, facades
  `View`/`Blade`, páginas de erro e de debug convertidas para Blade (`errors::`, com override pelo projecto) e skeleton com
  `layouts/app` + `home` em `/`.
- Peça 04 — base de dados: `DatabaseManager` (ligações nomeadas preguiçosas, `extend()`), `Connection` (PDO preguiçoso, prepared statements,
  transacções com savepoints, query log, `listen()`, `pretend()`), `ConnectionFactory` (SQLite, MySQL/MariaDB, PostgreSQL), `Statement`,
  Query Builder fluente com grammars por driver (identificadores e operadores validados), `Expression`, `JoinClause`, paginação
  (`Paginator`, `SimplePaginator`, views `pagination::default|simple`), `QueryException` com dica de migração, `RecordNotFoundException`
  (404 no `Handler`), facade `DB` e `config/database.php`.

### Changed
- `Router::toResponse()` renderiza as views dentro do pipeline (excepções e `abort()` em views passam pelo `Handler`; `old()`/flash
  funcionam durante o render).
- `Contracts\ViewFactory` ganha `exists()`; `ShareErrorsFromSession` partilha um `ViewErrorBag`.
