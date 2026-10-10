# core-04-13 — Package `slendie/react-admin`

> Peça 13 · Depende de: 11, 12 · Sessão única de IA · Pasta `packages/react-admin/`
> Referência obrigatória: análise crítica §4.1; decisão do utilizador: React como package opcional

## Objectivo
Scaffold de painel interno em React, publicado para o projecto (não corre de `vendor/`): lista de leads com pesquisa,
filtro por estado, paginação, detalhe e mudança de estado, autenticado com `slendie/auth`. Com Vite para dev (HMR) e
build para produção. O PHP serve uma única view `admin.blade.php` que carrega o bundle; tudo o resto é SPA sobre
`/api/*`. Sem SEO, por desenho.

## Estrutura (`packages/react-admin/`)
```
composer.json                 # slendie/react-admin; require slendie/framework ^4.0, slendie/auth ^4.0
src/ReactAdminServiceProvider.php   # loadRoutesFrom (GET /admin/{any?} → view, middleware auth), loadViewsFrom('react-admin'), commands, publishes
src/Http/Controllers/AdminController.php     # __invoke → view('react-admin::app', ['user' => auth()->user(), 'config' => [...]])
src/Http/Controllers/Api/{MeController,LeadController,StatsController}.php   # /api/me, /api/leads (reutiliza App\Http\Controllers\Api\LeadController se existir? não — o package traz o seu, prefixo /api/admin/leads), /api/admin/stats
src/Console/InstallCommand.php              # react-admin:install
resources/views/app.blade.php               # <div id="app" data-config='@json($config)'> + @viteReactRefresh + @vite(['resources/admin/main.tsx'])
stubs/                                      # tudo o que é publicado para o projecto:
  package.json                              # react 19, react-dom, react-router 7, vite 6, @vitejs/plugin-react, laravel-vite-plugin, typescript, tailwindcss 4 + @tailwindcss/vite
  vite.config.ts                            # laravel({ input: ['resources/admin/main.tsx'], refresh: ['resources/views/**'] }), react(), tailwindcss()
  tsconfig.json
  resources/admin/main.tsx  App.tsx  api.ts (fetch wrapper com X-CSRF-TOKEN/Bearer, 401 → redirect /login)  router.tsx
  resources/admin/pages/{Dashboard,Leads,LeadDetail,NotFound}.tsx
  resources/admin/components/{Layout,Sidebar,Table,Pagination,StatusBadge,SearchInput,Empty,Spinner,Toast}.tsx
  resources/admin/hooks/{useLeads,useLead,useStats}.ts
  resources/admin/styles.css                # @import "tailwindcss";
tests/                                      # Pest (PHP) + vitest mínimo para api.ts (opcional, não no CI PHP)
README.md
```

## Comportamentos
- `react-admin:install {--no-npm}`: verifica `slendie/auth` instalado (senão aborta com instrução), publica stubs para o projecto (não sobrepõe sem `--force`), faz merge de `package.json` se já existir (acrescenta deps/scripts), adiciona `/public/build` e `/public/hot` ao `.gitignore`, imprime próximos passos: `npm install && npm run dev` e `php slendie serve`; com npm disponível e sem `--no-npm`, pergunta se corre `npm install`
- Autenticação: SPA usa **sessão** (mesmo domínio): `api.ts` envia `X-CSRF-TOKEN` lido de `<meta name="csrf-token">`; rotas `/api/admin/*` em grupo `web` + `auth` (sessão, CSRF) — mais simples que tokens para same-origin; tokens continuam disponíveis para clientes externos
- API do package (`/api/admin`):
  - `GET me` → `{id, name, email}`
  - `GET leads?q=&status=&page=&per_page=&sort=created_at&dir=desc` → `Paginator::toArray()`
  - `GET leads/{lead}`, `PATCH leads/{lead}` (`status in:new,contacted,qualified,lost,won`, `notes`), `DELETE leads/{lead}`
  - `GET stats` → `{total, by_status: {...}, last_7_days: [{date, count}]}`
  - usa `App\Models\Lead` se existir; senão aborta na instalação com mensagem (o package assume o skeleton)
  - migração publicada `add_notes_to_leads_table` (coluna `notes text nullable`) se não existir
- Frontend: React 19 + TypeScript, react-router (rotas `/admin`, `/admin/leads`, `/admin/leads/:id`), Tailwind 4; layout com sidebar (Dashboard, Leads, Sair → POST /logout via form), tabela com pesquisa debounced, filtro de estado, paginação server-side, detalhe com alteração de estado e notas (optimistic update + toast), estados vazio/erro/loading; sem libs de estado (fetch + hooks); i18n simples pt (strings num objecto)
- Produção: `npm run build` → `public/build/manifest.json`; `@vite` serve; cabeçalho `Cache-Control` para `/build/*` via `.htaccess`
- Dev: `npm run dev` escreve `public/hot`; `@viteReactRefresh` injecta preamble; `php slendie serve` em paralelo

## Testes
- PHP (Pest): `react-admin:install` publica tudo numa pasta temporária, faz merge de `package.json`, aborta sem auth; `GET /admin` sem auth → redirect `/login`; com `actingAs` → 200 e HTML contém `id="app"` e config JSON com user; API: `me`, `leads` com filtros/pesquisa/paginação, `show`, `patch` valida estado, `destroy`, `stats` por estado e últimos 7 dias; CSRF obrigatório em PATCH.
- Frontend: `vitest` para `api.ts` (401 redirect) e `Pagination` — corre com `npm test`; não entra no CI PHP

## Critérios de aceitação
```
composer require slendie/auth slendie/react-admin
php slendie auth:install
php slendie react-admin:install
npm install && npm run dev      # terminal 1
php slendie serve               # terminal 2
```
→ `/admin` pede login, lista leads do skeleton com pesquisa e filtro, muda estado. `npm run build` + `APP_DEBUG=false` funciona sem Vite a correr. `docs/react-admin.md` e `packages/react-admin/README.md` escritos, incluindo "Como adaptar o painel a outro model".

## Fora de âmbito
SSR, Inertia, autenticação por token no SPA, gestão de utilizadores no painel (v4.1), exportação CSV (v4.1, trivial), gráficos além de barras simples em CSS.

## Desvios
(preencher na implementação)
