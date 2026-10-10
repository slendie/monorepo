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

## Adaptar a outro model

O código publicado é teu (`resources/admin/**`). Para gerir, por exemplo, `Order`:
1. Copia `pages/Leads.tsx` → `pages/Orders.tsx` e `hooks/useLeads.ts` → `useOrders.ts`.
2. Cria `App\Http\Controllers\Api\Admin\OrderController` (o `LeadController` do package serve de modelo) e regista em `routes/api.php` dentro de `middleware(['web', 'auth'])`.
3. Acrescenta a rota em `router.tsx` e o item na `Sidebar`.

## Sem React?

O painel é opcional. Alternativas leves: Blade + `fetch`, ou Blade puro com formulários. A API `/api/leads` do
skeleton funciona com qualquer frontend.
