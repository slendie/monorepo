<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin;

use Slendie\Foundation\ServiceProvider;
use Slendie\ReactAdmin\Console\InstallCommand;

/**
 * Painel React: serve a view `react-admin::app` em `/admin/*` e a API `/api/admin/*` (sessão + CSRF + auth).
 * O código React não corre de `vendor/`: `react-admin:install` publica-o para `resources/admin/`.
 */
final class ReactAdminServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $root = dirname(__DIR__);

        $this->loadViewsFrom($root.'/resources/views', 'react-admin');
        $this->loadRoutesFrom($root.'/routes/web.php');
        $this->loadRoutesFrom($root.'/routes/api.php', ['middleware' => ['web', 'auth'], 'prefix' => 'api/admin']);

        $this->commands([InstallCommand::class]);

        $this->publishes([
            $root.'/stubs/resources/admin' => $this->app->resourcePath('admin'),
            $root.'/stubs/vite.config.ts' => $this->app->basePath('vite.config.ts'),
            $root.'/stubs/tsconfig.json' => $this->app->basePath('tsconfig.json'),
        ], 'react-admin-stubs');
        $this->publishes([$root.'/resources/views' => $this->app->resourcePath('views/vendor/react-admin')], 'react-admin-views');
        $this->publishesMigrations([$root.'/database/migrations' => $this->app->databasePath('migrations')], 'react-admin-migrations');
    }
}
