# Instalação

## Requisitos
- PHP 8.5 ou superior com `pdo`, `pdo_sqlite` (ou `pdo_mysql` / `pdo_pgsql`), `mbstring`, `json`
- Composer 2
- Node 20+ **apenas** se usares o package `slendie/react-admin`

## Criar um projecto

```bash
composer create-project slendie/slendie meu-site
cd meu-site
php slendie serve
```

O `create-project` corre automaticamente: cópia de `.env.example` para `.env`, `php slendie key:generate`, criação de
`database/database.sqlite` e `php slendie migrate`. Abre `http://127.0.0.1:8000`.

## Instalação manual (clone de um repositório existente)

```bash
composer install
copy .env.example .env          # Windows
cp .env.example .env            # Unix
php slendie key:generate
php slendie migrate
php slendie serve
```

## Usar MySQL ou PostgreSQL

Edita `.env`:

```
DB_CONNECTION=mysql       # ou pgsql
DB_HOST=127.0.0.1
DB_PORT=3306              # 5432 para pgsql
DB_DATABASE=meu_site
DB_USERNAME=root
DB_PASSWORD=
```

Depois `php slendie migrate`.

## Adicionar o painel de leads

```bash
composer require slendie/auth slendie/react-admin
php slendie auth:install          # cria tabela users, pede o primeiro utilizador
php slendie react-admin:install   # publica o scaffold React
npm install && npm run dev
```

Ver [auth.md](auth.md) e [react-admin.md](react-admin.md).

## Actualizar a framework

```bash
composer update slendie/framework
```

O código da framework vive em `vendor/slendie/framework`. O teu projecto só contém o que é teu.

## Servidor web

Para desenvolvimento, `php slendie serve` chega. Para produção, aponta o *document root* para `public/` (Apache com o
`.htaccess` incluído, ou Nginx com `try_files $uri /index.php?$query_string`). Ver [deployment.md](deployment.md).
