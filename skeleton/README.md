# Slendie

Landing page leve com formulário de contacto, feita com a [Slendie](https://slendie.lcloss.dev) — um "Laravel light" em PHP, sem Node.
O formulário valida no servidor, tem honeypot e consentimento RGPD, e grava cada contacto como `Lead` na base de dados.

## Instalação (5 comandos)

```bash
composer create-project slendie/slendie meu-site
cd meu-site
php slendie serve
# abre http://127.0.0.1:8000
php slendie db:seed   # opcional: 3 leads de exemplo
```

O `composer create-project` copia o `.env`, gera a `APP_KEY`, cria `database/database.sqlite` e corre as migrações.
Requisitos: PHP ^8.5 com `pdo_sqlite` (ou `pdo_mysql` / `pdo_pgsql`) e Composer.

## O que vem incluído

| Rota | O quê |
|---|---|
| `GET /` | Landing page com formulário |
| `POST /contact` | Grava o lead (HTML → redirect para `/obrigado`; JSON → 201) |
| `GET /obrigado`, `/privacidade`, `/termos` | Páginas de apoio (os textos legais são um **modelo**: revê-os) |
| `GET/PUT/DELETE /api/leads…` | API JSON dos leads, com `Authorization: Bearer <API_TOKEN>` |

Personaliza em `resources/views/home.blade.php`, `public/css/app.css` e `config/app.php` (`seo.title`, `seo.description`, `seo.image`).

## Estrutura

```
app/            Controllers, FormRequests, Models, Middleware, Providers
bootstrap/      Arranque da aplicação
config/         Configuração
database/       Migrações e seeders (SQLite em database/database.sqlite)
public/         Docroot: index.php, css, js, imagens
resources/views Views Blade (layouts, partials, páginas, errors)
routes/         web.php, api.php, console.php
storage/        Logs, sessões, views compiladas
tests/          Pest
```

## Comandos úteis

```bash
composer test       # Pest
composer analyse    # PHPStan (nível 6)
composer lint       # Pint
php slendie list    # todos os comandos
php slendie ai:context --json   # contexto do projecto para agentes de IA (ver AGENTS.md)
```

## API de leads

Define `API_TOKEN` no `.env` (gera um com `php slendie key:generate --show`). Enquanto estiver vazio, a API responde 503.

```bash
curl -H "Authorization: Bearer $API_TOKEN" "http://127.0.0.1:8000/api/leads?status=new&search=ana&per_page=20"
curl -X PUT -H "Authorization: Bearer $API_TOKEN" -H "Content-Type: application/json" \
     -d '{"status":"contacted"}' http://127.0.0.1:8000/api/leads/1
```

Estados: `new`, `contacted`, `qualified`, `lost`.

## Painel de leads

O skeleton não traz painel. Instala um quando precisares:

```bash
composer require slendie/auth slendie/react-admin
```

Ou constrói um em Blade: o model é `App\Models\Lead` (`Lead::filtered($status, $search)->paginate()`).

## Deploy

1. Aponta o **docroot** para `public/` (nunca para a raiz do projecto).
2. `composer install --no-dev --optimize-autoloader`, copia o `.env` e define `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL` e a `APP_KEY`.
3. `php slendie migrate --force`.
4. Dá permissão de escrita a `storage/` e `bootstrap/cache/` (e a `database/`, se usares SQLite).
5. **Apache:** o `public/.htaccess` já reescreve tudo para `index.php`. **Nginx:**

```nginx
root /var/www/meu-site/public;
index index.php;
location / { try_files $uri /index.php?$query_string; }
location ~ \.php$ { include fastcgi_params; fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name; fastcgi_pass unix:/run/php/php8.5-fpm.sock; }
```

## FAQ

**Preciso de Node?** Não. O CSS e o JS são ficheiros normais em `public/`. Se quiseres Vite, o layout usa-o sozinho quando existe `public/build/manifest.json`.

**Posso usar MySQL?** Sim: define `DB_CONNECTION=mysql` e `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` no `.env`.

**Como mudo o idioma?** `APP_LOCALE` no `.env`; as mensagens da framework existem em `pt` e `en`.

**Vem com autenticação?** Não; vem com `slendie/auth` (package à parte).

Licença: MIT.
