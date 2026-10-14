# Slendie v4 — monorepo

Slendie é um **Laravel light**: framework MVC em PHP 8.5, sem dependências em runtime, para landing pages com painel
de leads e sites simples renderizados em PHP. Este repositório contém tudo o que é publicado no Packagist:

| Pasta | Package | O que é |
|---|---|---|
| `framework/` | `slendie/framework` | O código da framework (`Slendie\`) |
| `skeleton/` | `slendie/slendie` | O projecto que o utilizador recebe com `composer create-project` |
| `packages/auth/` | `slendie/auth` | Login por sessão e tokens de API (opcional) |
| `packages/react-admin/` | `slendie/react-admin` | Painel de leads em React (opcional) |
| `docs/` | — | Documentação para utilizadores |
| `specs/` | — | Análise, arquitectura, backlog e especificações de implementação |

## Instalação

```bash
composer create-project slendie/slendie meu-site
cd meu-site && php slendie serve

# opcionais
composer require slendie/auth && php slendie auth:install && php slendie migrate
composer require slendie/react-admin && php slendie react-admin:install
```

Os repositórios `slendie/framework`, `slendie/slendie`, `slendie/auth` e `slendie/react-admin` são **espelhos só de leitura**
gerados deste monorepo: issues e pull requests abrem-se aqui.

## Começar a desenvolver

```bash
composer install        # liga os packages locais por path
composer test
composer analyse
composer lint
```

Experimentar o skeleton como utilizador final:

```bash
composer create-project --repository='{"type":"path","url":"skeleton"}' slendie/slendie ../demo --no-interaction
cd ../demo && php slendie serve
```

## Para onde ir

- Utilizador da framework → [`docs/README.md`](docs/README.md)
- Porque é que a v4 é uma reescrita → [`specs/core-01-critical-analysis.md`](specs/core-01-critical-analysis.md)
- Como está desenhada → [`specs/core-02-architecture.md`](specs/core-02-architecture.md)
- O que falta e por que ordem → [`specs/core-03-backlog.md`](specs/core-03-backlog.md)
- Implementar uma peça → `specs/core-04-NN-*.md`

## Estado

Peça 01 (basement) implementada. Ver backlog para as restantes.

## Licença

MIT.
