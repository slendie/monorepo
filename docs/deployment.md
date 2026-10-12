# Deploy

## Checklist

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://meusite.pt
APP_KEY=base64:...            # php slendie key:generate
LOG_CHANNEL=daily
```

```bash
composer install --no-dev --optimize-autoloader
php slendie migrate --force
php slendie view:clear
npm run build                 # só se usares Vite
```

`storage/` e `bootstrap/cache/` graváveis pelo utilizador do servidor web. *Document root* = `public/`.

## Apache

O `public/.htaccess` incluído redirecciona tudo para `index.php` e protege ficheiros ocultos. Precisa de
`mod_rewrite` e `AllowOverride All`. Se o alojamento não permitir apontar o document root para `public/`, coloca o
projecto fora de `public_html` e faz de `public_html` um link simbólico para `public/` (ou copia `index.php` ajustando
os `require`).

## Nginx

```nginx
server {
    server_name meusite.pt;
    root /var/www/meusite/public;
    index index.php;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        include fastcgi_params;
        fastcgi_pass unix:/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    }

    location ~ /\.(?!well-known) { deny all; }
    location ~* \.(css|js|svg|png|jpg|woff2)$ { expires 30d; add_header Cache-Control "public"; }
}
```

## SQLite em produção

Perfeitamente adequado para landing pages e painéis com um utilizador. Garante que `database/database.sqlite` e a
pasta `database/` são graváveis, faz backup do ficheiro, e considera `journal_mode => 'wal'` em `config/database.php`.
Para vários utilizadores concorrentes a escrever, usa MySQL/PostgreSQL.

## Alojamento partilhado

Requisitos: PHP 8.5, `pdo_sqlite` ou `pdo_mysql`, possibilidade de correr `composer`/`php` por SSH (ou fazer
`composer install` localmente e enviar `vendor/`). Não há *build step* obrigatório.

## Actualizações

```bash
git pull
composer install --no-dev
php slendie migrate --force
php slendie view:clear
```
