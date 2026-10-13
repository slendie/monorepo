# Agentes de IA

A Slendie é pequena de propósito, mas um agente habituado ao Laravel vai assumir funcionalidades que não existem
(Eloquent completo, Artisan, components Blade). Dois mecanismos evitam isso.

## `AGENTS.md`

O skeleton (peça `core-04-11`) traz um `AGENTS.md` na raiz com:
- o que a framework tem e **não tem**, com alternativas;
- convenções de pastas e nomes;
- comandos (`php slendie …`, `composer test`);
- como obter contexto real com `ai:context`.

Regenerar: `php slendie ai:agents-file --force` (chega com o skeleton).

## `php slendie ai:context`

Introspecção ao vivo do projecto, para o agente confirmar em vez de adivinhar:

```bash
php slendie ai:context routes          # rotas registadas (método, uri, nome, acção, middleware)
php slendie ai:context schema          # tabelas e colunas da BD actual
php slendie ai:context config          # configuração; valores de chaves com KEY, PASSWORD, SECRET ou TOKEN ficam ********
php slendie ai:context info            # versões, env, driver, providers, packages, middlewares, directivas, comandos
php slendie ai:context all --json      # tudo num único objecto JSON (chaves routes, schema, config, info)
php slendie ai:context --json          # o mesmo (all é o valor por omissão)
```

Sem `--json` a saída é texto legível (rotas numa linha cada, tabelas com colunas, `chave = valor`). Se a base de dados
não estiver acessível, `schema` fica vazio em vez de falhar.

Não há servidor MCP: um agente com acesso à shell corre `ai:context --json` e tem o mesmo resultado sem processo
persistente nem dependências.

## Sugestão de fluxo para um agente

1. Ler `AGENTS.md`.
2. `php slendie ai:context all --json`.
3. Consultar `vendor/slendie/framework/docs/` (esta documentação é publicada com a framework) para a API exacta.
4. Implementar; correr `composer test`, `composer analyse`, `composer lint`.
