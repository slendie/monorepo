# core-04-14 — Publicação: split, Packagist e `v4.0.0`

> Peça 14 · Depende de: 01–13 · Sem código de runtime novo · Toca em repositórios externos (GitHub, Packagist)
> Referência obrigatória: `core-02-architecture.md` §2 (packages e repositórios)

## Objectivo
`composer create-project slendie/slendie meu-site` funciona a partir do Packagist, e `composer require slendie/auth`
/ `slendie/react-admin` instalam os packages. Cada pasta do monorepo é publicada automaticamente no seu repositório
espelho (só de leitura) e as tags `v4.*` do monorepo propagam-se às 4 tags.

## Mapa de repositórios

| Pasta do monorepo | Espelho GitHub | Pacote Packagist | Estado inicial |
|---|---|---|---|
| (raiz) | `slendie/monorepo` | **não publicar** | já existe (antigo `slendie/slendie`, histórico v1–v3) |
| `framework/` | `slendie/framework` | `slendie/framework` | existe; histórico v3 preservado na branch `v3.1.8` e nas tags `v3.*` |
| `skeleton/` | `slendie/slendie` | `slendie/slendie` | criado vazio |
| `packages/auth/` | `slendie/auth` | `slendie/auth` | criado vazio; **não** submetido |
| `packages/react-admin/` | `slendie/react-admin` | `slendie/react-admin` | criado vazio; **não** submetido |

`slendie/tools` e `slendie/crawler` existem no Packagist mas não fazem parte da v4: não tocar.

## Parte A — Workflow de split (`.github/workflows/split.yml`) — FEITO
- Disparadores: push em `main`, push de tags `v4.*`, e `workflow_dispatch` com `force` (só para o primeiro split).
- Ferramenta: `git subtree split --prefix=<pasta>` num runner Linux (sem dependências; decidido por o ambiente de
  desenvolvimento ser Windows e para ser reproduzível localmente com o mesmo comando). `fetch-depth: 0` é obrigatório.
  Se ficar lento com o crescimento do histórico, trocar por `splitsh-lite` mantendo a mesma matriz.
- Matriz: as 4 pastas da tabela acima. Push `main` → `refs/heads/main` do espelho; push de tag `vX` → `refs/tags/vX`.
- Autenticação: secret `SPLIT_TOKEN` (fine-grained PAT, `Contents: Read and write` nos 4 espelhos).
- O split é determinístico: os pushes seguintes são fast-forward, sem `--force`.

## Parte B — Passos manuais (por ordem; cada um confirmado antes de executar)
1. Criar o secret `SPLIT_TOKEN` em `slendie/monorepo` → Settings → Secrets → Actions.
2. **Primeiro split.** Os espelhos `framework` e `slendie` têm commits de ensaios anteriores com histórico divergente.
   Correr o workflow em `main` via *Run workflow* com `force = true` (confirmar antes que a branch `v3.1.8` do
   `slendie/framework` existe no GitHub, pois o force-push substitui `main`). Verificar que os 4 espelhos ficam com o
   conteúdo da pasta na raiz (`composer.json` na raiz).
3. **Packagist** (conta `slendie`):
   - `slendie/framework`: já existe, repositório `github.com/slendie/framework`; fazer *Update* após o split.
   - `slendie/slendie`: **atenção**, está registado a apontar para `github.com/slendie/monorepo`. Editar o pacote e
     mudar o repositório para `https://github.com/slendie/slendie` (nunca deixar o monorepo publicado).
   - `slendie/auth` e `slendie/react-admin`: *Submit* dos URLs dos espelhos (depois do primeiro split).
4. **Webhooks:** nos 4 espelhos, activar a auto-actualização do Packagist (GitHub Hook do Packagist, ou webhook com o
   API token em *Settings → Webhooks*: `https://packagist.org/api/github?username=<user>`). O push do workflow dispara-o.
5. **Pré-requisitos de `composer.json`** antes da tag (ver Parte C).
6. **Tag** `v4.0.0` no `main` do monorepo (`git tag v4.0.0 && git push origin v4.0.0`), depois de `composer check`
   verde. O workflow cria as 4 tags. Packagist apanha-as pelo webhook.
7. **Verificação** (Parte D).

## Parte C — Ajustes no repositório (esta peça)
- `packages/react-admin/composer.json`: acrescentar `extra.branch-alias.dev-main = 4.x-dev` (como framework e auth).
- `homepage`, `support.{issues,source}` e `authors` completos em `skeleton`, `auth` e `react-admin` (a framework já os tem).
- `framework/composer.json`: `suggest` mantém-se; confirmar que `slendie/framework` não declara dependências dos packages.
- `skeleton/composer.json`: `require slendie/framework ^4.0` (já está) — **não** pode ter `repositories` do tipo `path`.
- `README.md` de cada pasta publicada indica que o repositório é um espelho só de leitura e aponta para
  `slendie/monorepo` para issues e PRs. Acrescentar ao `.github/` do monorepo um template de issue que diga o mesmo.
- `.gitattributes`: manter `export-ignore` de `/specs`, `/docs`, `/.github`; `tests/` do skeleton **não** é excluído
  (decisão da peça 11).
- `docs/packages.md` (secção "Publicar no Packagist"): descrever o fluxo real (split automático, tag no monorepo, webhook).
- `CHANGELOG.md`: passar `Unreleased` a `[4.0.0] - <data>` e abrir novo `Unreleased`.
- `README.md` da raiz: secção "Instalação" com `composer create-project slendie/slendie` e `composer require` dos packages.

## Parte D — Verificação (aceitação)
Numa pasta temporária, fora do monorepo e sem `path` repositories:
1. `composer create-project slendie/slendie teste` → conclui, gera `.env`, `APP_KEY` e a SQLite; `php slendie serve`
   mostra a landing e grava um lead.
2. `composer require slendie/auth && php slendie auth:install && php slendie migrate` → `/admin` protegido.
3. `composer require slendie/react-admin && php slendie react-admin:install && npm install && npm run build` → painel
   em `/admin` autenticado (inclui o teste no browser que ficou por exercitar na peça 13).
4. `composer show slendie/framework` indica `v4.0.0`; `composer.json` do projecto criado exige `slendie/framework ^4.0`.
5. As páginas dos 4 pacotes no Packagist mostram `v4.0.0` e o repositório espelho correcto.

## Riscos e notas
- **Force-push no primeiro split** apaga o `main` antigo dos espelhos: só depois de confirmar o backup v3 (branch `v3.1.8` + tags).
- **Ordem de tags:** o workflow cria as 4 tags em paralelo; o Packagist pode ver `slendie/slendie` antes de
  `slendie/framework v4.0.0`. Se `create-project` falhar nos primeiros minutos, repetir após o *Update* do framework.
- `stable` por defeito: `minimum-stability` do skeleton é `stable`, por isso `v4.0.0` tem de existir em todos os
  pacotes antes de qualquer `create-project` sem `--stability=dev`.
- Os packages `auth` e `react-admin` exigem `slendie/framework ^4.0`: a tag da framework tem de estar no Packagist
  antes de submeter/actualizar os packages.
- Fora de âmbito (peça 15): site de documentação.

## Desvios
(preencher na implementação)
