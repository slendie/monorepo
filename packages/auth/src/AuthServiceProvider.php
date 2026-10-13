<?php

declare(strict_types=1);

namespace Slendie\Auth;

use Slendie\Auth\Console\InstallCommand;
use Slendie\Auth\Console\MakeUserCommand;
use Slendie\Auth\Console\TokenCreateCommand;
use Slendie\Auth\Hashing\Hasher;
use Slendie\Auth\Middleware\Authenticate;
use Slendie\Auth\Middleware\AuthenticationContext;
use Slendie\Auth\Middleware\EnsureTokenCan;
use Slendie\Auth\Middleware\RedirectIfAuthenticated;
use Slendie\Auth\Routing\AuthRedirector;
use Slendie\Auth\Support\LoginThrottle;
use Slendie\Foundation\ServiceProvider;
use Slendie\Http\Kernel;
use Slendie\Routing\Redirector;
use Slendie\Routing\UrlGenerator;
use Slendie\Support\Config;
use Slendie\View\Compiler\BladeCompiler;

/**
 * Package de referência: regista config, serviços, rotas, views, migrações, traduções, middlewares, directivas
 * Blade, comandos e tudo o que é publicável.
 */
final class AuthServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $root = dirname(__DIR__);

        $this->mergeConfigFrom($root.'/config/auth.php', 'auth');

        $this->app->singleton(Hasher::class, function (): Hasher {
            $config = $this->app->make(Config::class)->get('auth.hashing', []);

            /** @var array<string, mixed> $config */
            $config = is_array($config) ? $config : [];

            return new Hasher($config);
        });
        $this->app->alias(Hasher::class, 'hash');

        $this->app->singleton(AuthManager::class, fn (): AuthManager => new AuthManager(
            $this->app,
            $this->app->make(Config::class),
            $this->app->make(Hasher::class),
        ));
        $this->app->alias(AuthManager::class, 'auth');

        $this->app->singleton(LoginThrottle::class, function (): LoginThrottle {
            $config = $this->app->make(Config::class);
            $max = $config->get('auth.throttle.max_attempts', 5);
            $decay = $config->get('auth.throttle.decay_seconds', 60);

            return new LoginThrottle(
                $this->app->storagePath('framework/auth-throttle'),
                is_numeric($max) ? (int) $max : 5,
                is_numeric($decay) ? (int) $decay : 60,
            );
        });

        // `redirect()->intended()`: o Redirector da framework, com o método a mais.
        $this->app->singleton(Redirector::class, fn (): AuthRedirector => new AuthRedirector($this->app, $this->app->make(UrlGenerator::class)));
        $this->app->alias(Redirector::class, AuthRedirector::class);
    }

    public function boot(): void
    {
        $root = dirname(__DIR__);

        $this->loadRoutesFrom($root.'/routes/web.php');
        $this->loadViewsFrom($root.'/resources/views', 'auth');
        $this->loadMigrationsFrom($root.'/database/migrations');
        $this->loadTranslationsFrom($root.'/lang', 'auth');

        $router = $this->router();
        $router->aliasMiddleware('auth', Authenticate::class);
        $router->aliasMiddleware('guest', RedirectIfAuthenticated::class);
        $router->aliasMiddleware('ability', EnsureTokenCan::class);

        $this->app->make(Kernel::class)->pushMiddleware(AuthenticationContext::class);

        $this->callAfterResolving(BladeCompiler::class, static function (BladeCompiler $blade): void {
            $blade->if('auth', static fn (?string $guard = null): bool => auth($guard)->check());
            $blade->if('guest', static fn (?string $guard = null): bool => auth($guard)->guest());
        });

        $this->commands([InstallCommand::class, MakeUserCommand::class, TokenCreateCommand::class]);

        $this->publishes([$root.'/config/auth.php' => $this->app->configPath('auth.php')], 'auth-config');
        $this->publishes([$root.'/resources/views' => $this->app->resourcePath('views/vendor/auth')], 'auth-views');
        $this->publishes([$root.'/lang' => $this->app->langPath('vendor/auth')], 'auth-lang');
        $this->publishes([$root.'/stubs/User.stub' => $this->app->appPath('Models/User.php')], 'auth-model');
        $this->publishesMigrations([$root.'/database/migrations' => $this->app->databasePath('migrations')], 'auth-migrations');
    }
}
