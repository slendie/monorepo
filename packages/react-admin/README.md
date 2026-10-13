# slendie/react-admin

Painel interno em **React 19 + TypeScript + Tailwind 4** para a [Slendie](https://slendie.lcloss.dev): lista de leads com pesquisa,
filtro por estado e paginação, detalhe com mudança de estado e notas, dashboard. Autenticado com `slendie/auth` (sessão + CSRF),
sem SEO, por desenho. Requer Node 20+ e o skeleton (usa `App\Models\Lead`).

O código React **não corre de `vendor/`**: `react-admin:install` publica-o para `resources/admin/` e passa a ser teu.

## Instalação

```bash
composer require slendie/auth slendie/react-admin
php slendie auth:install
php slendie react-admin:install       # publica o painel, vite.config.ts, tsconfig.json, merge do package.json, migração de notas
php slendie migrate                   # leads.notes
npm install && npm run dev            # terminal 1 (HMR)
php slendie serve                     # terminal 2 → http://127.0.0.1:8000/admin
```

Opções do `react-admin:install`: `--force` (substitui ficheiros já publicados) e `--no-npm` (não oferece correr `npm install`).
Aborta com instruções se `slendie/auth` não estiver instalado ou se não existir `App\Models\Lead`.

## O que o package traz (PHP)

| Peça | Descrição |
|---|---|
| `GET /admin/{any?}` | `AdminController` → view `react-admin::app` (`<div id="app" data-config>`, `@viteReactRefresh`, `@vite`); middleware `auth` |
| `GET /api/admin/me` | `{id, name, email}` |
| `GET /api/admin/leads` | `?q=&status=&page=&per_page=&sort=&dir=` → `Paginator::toArray()` |
| `GET\|PATCH\|DELETE /api/admin/leads/{id}` | detalhe; `PATCH` aceita `status` (`new,contacted,qualified,lost,won`) e `notes` |
| `GET /api/admin/stats` | `{total, by_status, last_7_days}` |
| `react-admin:install` | publica os stubs; tags `react-admin-stubs`, `react-admin-views`, `react-admin-migrations` |

A API corre em grupo `web` + `auth` (sessão e CSRF: `X-CSRF-TOKEN` lido de `<meta name="csrf-token">`). Os tokens de `slendie/auth`
continuam a servir clientes externos (`/api/leads` do skeleton).

## Frontend (publicado em `resources/admin/`)

`main.tsx` · `App.tsx` · `router.tsx` · `api.ts` (fetch com CSRF; 401 → `/login`) · `i18n.ts` (pt) · `pages/` · `components/` · `hooks/`.
Sem libs de estado: `fetch` + hooks. Testes: `npm test` (vitest, `api.ts` e `Pagination`).

## Produção

```bash
npm run build      # public/build/ (+ manifest.json)
```

Com `APP_DEBUG=false` e sem o Vite a correr, `@vite` lê o manifest. O instalador acrescenta ao `public/.htaccess` o `Cache-Control` imutável de `/build/*`.

## Testes do package

```bash
vendor/bin/pest packages/react-admin
```

Ver a documentação completa, incluindo **como adaptar o painel a outro model**, em [`docs/react-admin.md`](../../docs/react-admin.md).
