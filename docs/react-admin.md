# Package `slendie/react-admin`

Painel interno em React 19 + TypeScript + Tailwind 4, servido pela Slendie em `/admin`, autenticado com
`slendie/auth`, a falar com `/api/admin/*`. Sem SEO, por desenho. Requer Node 20+.

```bash
composer require slendie/auth slendie/react-admin
php slendie auth:install
php slendie react-admin:install       # publica package.json, vite.config.ts, resources/admin/**
npm install
npm run dev                            # terminal 1 (HMR)
php slendie serve                      # terminal 2
```

Abre `http://127.0.0.1:8000/admin` → login → painel.

## O que inclui
- **Dashboard**: totais por estado, leads dos últimos 7 dias.
- **Leads**: tabela com pesquisa (nome/email), filtro por estado, paginação server-side, ordenação.
- **Detalhe**: dados do lead, alteração de estado (`new`, `contacted`, `qualified`, `won`, `lost`), notas.
- Sidebar, toasts, estados vazio/erro/loading, `pt` por defeito.

## Como funciona
- `GET /admin/{any?}` → `react-admin::app` (uma view com `<div id="app" data-config>` + `@vite`).
- A SPA usa a **sessão** (same-origin) e envia `X-CSRF-TOKEN` lido de `<meta name="csrf-token">`.
- API: `GET /api/admin/me`, `GET /api/admin/leads?q=&status=&page=&sort=&dir=`, `GET|PATCH|DELETE /api/admin/leads/{id}`,
  `GET /api/admin/stats`. Devolve `Paginator::toArray()` e `Model::toArray()`.
- Publica uma migração `add_notes_to_leads_table` se a coluna não existir.

## Produção

```bash
npm run build          # public/build/
```

Com `APP_DEBUG=false` e sem `npm run dev`, `@vite` lê o manifest. Faz *deploy* de `public/build/` com o código.

## O instalador

`react-admin:install` (opções `--force`, `--no-npm`):

- aborta com instruções se `slendie/auth` não estiver instalado (`composer require slendie/auth && php slendie auth:install`) ou se não existir `App\Models\Lead`;
- publica `resources/admin/**`, `vite.config.ts` e `tsconfig.json` (nunca sobrepõe sem `--force`; se já tens um `vite.config.ts`, acrescenta-lhe a entrada `resources/admin/main.tsx`);
- faz *merge* do `package.json` (acrescenta dependências e scripts em falta; o que já tens prevalece);
- acrescenta `/public/build`, `/public/hot` e `/node_modules` ao `.gitignore` e o `Cache-Control` imutável de `/build/*` ao `public/.htaccess`;
- publica a migração `add_notes_to_leads_table` só se `leads.notes` ainda não existir;
- com npm disponível (e sem `--no-npm`) pergunta se corre `npm install`.

Tags de `vendor:publish`: `react-admin-stubs`, `react-admin-views` (a view `app.blade.php`, para mudares o `<head>`) e `react-admin-migrations`.

## Adaptar a outro model

O código publicado é teu (`resources/admin/**`). Para gerir, por exemplo, `Order`:
1. Copia `pages/Leads.tsx` → `pages/Orders.tsx` e `hooks/useLeads.ts` → `useOrders.ts` (e `types.ts` com o novo tipo).
2. Cria `App\Http\Controllers\Api\Admin\OrderController`, usando o `LeadController` do package como modelo
   (`packages/react-admin/src/Http/Controllers/Api/LeadController.php`), e regista as rotas em `routes/api.php` dentro de
   `Route::middleware(['web', 'auth'])->prefix('admin')->group(...)`.
3. Acrescenta a rota em `router.tsx` e o item na `Sidebar`.

Para o **mesmo painel sobre outro model de leads** (outro namespace), indica-o num provider da app:
`Slendie\ReactAdmin\Support\Leads::$model = \App\Models\Contact::class;` (precisa das colunas `name`, `email`, `status`, `notes`).

## Testes

- PHP: `vendor/bin/pest packages/react-admin` (acesso, API, CSRF, instalador).
- Frontend: `npm test` (vitest: `api.ts` e `Pagination`); não entra no CI PHP.

## Sem React?

O painel é opcional. Alternativas leves: Blade + `fetch`, ou Blade puro com formulários. A API `/api/leads` do
skeleton funciona com qualquer frontend.
