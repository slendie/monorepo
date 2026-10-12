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
