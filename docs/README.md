# Slendie v4 — Documentação

Slendie é um **Laravel light**: uma framework MVC em PHP 8.5, sem dependências em runtime, pensada para landing
pages com painel de leads e sites simples renderizados em PHP. Se conheces Laravel, já conheces a Slendie; se não
conheces, a Slendie cabe na cabeça.

```bash
composer create-project slendie/slendie meu-site
cd meu-site
php slendie serve
```

## Índice

### Começar
- [Instalação](installation.md)
- [Estrutura do projecto](structure.md)
- [Tutorial: landing page com leads em 15 minutos](landing-page.md)
- [Configuração e `.env`](configuration.md)

### O básico
- [Rotas](routing.md)
- [Controllers](controllers.md)
- [Request e Response](requests-responses.md)
- [Middleware](middleware.md)
- [Sessão](sessions.md)
- [Views e Blade](views.md)
- [Validação](validation.md)
- [Traduções](localization.md)
- [Erros](errors.md)
- [Logging](logging.md)

### Base de dados
- [Ligação e queries](database.md)
- [Query Builder](query-builder.md)
- [Models](models.md)
- [Migrações e Schema](migrations.md)
- [Seeders](seeding.md)

### Ferramentas
- [CLI `php slendie`](cli.md)
- [Testes](testing.md)
- [Desempenho (benchmark)](performance.md)
- [Vite e assets](vite.md)
- [Agentes de IA (`ai:context`, `AGENTS.md`)](ai-agents.md)

### Extensões
- [Criar packages e Service Providers](packages.md)
- [Package `slendie/auth`](auth.md)
- [Package `slendie/react-admin`](react-admin.md)

### Referência
- [Diferenças para o Laravel](laravel-differences.md)
- [Deploy](deployment.md)
- [Contribuir](contributing.md)
- [Changelog](../CHANGELOG.md)

## Estado

A v4 é uma reescrita completa. O planeamento e as especificações de implementação estão em [`/specs`](../specs/).
Esta documentação descreve a API **tal como especificada**; cada peça implementada actualiza a sua página.
