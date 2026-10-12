# Contribuir

O desenvolvimento acontece no monorepo `slendie-v4`, que contém `framework/`, `skeleton/` e `packages/*`.
Cada pasta é publicada como package Composer próprio.

```bash
git clone https://github.com/slendie/slendie-v4
cd slendie-v4
composer install              # liga framework/, skeleton/ e packages/* por path
composer test                 # Pest (framework + packages)
composer analyse              # PHPStan nível 8
composer lint                 # Pint (PSR-12); composer fix para corrigir
```

Testar o skeleton como utilizador final:

```bash
composer create-project --repository='{"type":"path","url":"skeleton"}' slendie/slendie /tmp/demo --no-interaction
```

## Regras
- PHP 8.5, `declare(strict_types=1)`, tipos em tudo, PSR-12.
- Zero dependências em runtime na framework.
- Toda a funcionalidade nova vem com testes e com a página de `docs/` actualizada.
- Uma funcionalidade só entra no core se o caso dos 90% (landing + formulário + painel) a usar; senão é package.
- Nomes de classes/pastas em inglês; docs, commits e comentários em português de Portugal.

## Planeamento
`specs/core-03-backlog.md` lista as peças por ordem; cada `specs/core-04-NN-*.md` é auto-contida e dimensionada para
uma sessão de implementação (humana ou IA). Ao implementar, actualiza o estado no backlog e anota desvios na secção
"Desvios" da spec.
