# Criar packages e Service Providers

Tudo o que a framework, a aplicação e os packages fazem no arranque passa por **Service Providers**. Um package
Slendie é um package Composer normal com um provider declarado em `extra.slendie.providers`.

## Service Provider da aplicação

`app/Providers/AppServiceProvider.php`:

```php
final class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(LeadRepository::class);
        $this->app->bind(Mailer::class, SmtpMailer::class);
    }

    public function boot(): void
    {
        view()->share('siteName', config('app.name'));
        Blade::directive('money', fn ($e) => "<?php echo number_format($e, 2, ',', '.'); ?>");
        $this->router()->aliasMiddleware('admin', EnsureIsAdmin::class);
    }
}
```

Registado em `bootstrap/providers.php`. `register()` só faz *bindings*; `boot()` corre depois de todos os
`register()` e pode usar qualquer serviço (com DI nos parâmetros).

## Container

```php
$this->app->bind(Abstract::class, Concrete::class);
$this->app->bind(Abstract::class, fn (Application $app) => new Concrete($app->make(Dep::class)));
$this->app->singleton(X::class);
$this->app->instance('config.x', $obj);
$this->app->extend(X::class, fn (X $x) => new Decorated($x));
$this->app->make(X::class);  app(X::class);  app()->call([$obj, 'method'], ['param' => 1]);
```

Classes concretas resolvem-se sem *binding* (autowiring por reflection).

## Criar um package

```
acme/blog/
├── composer.json
├── src/BlogServiceProvider.php
├── src/Http/Controllers/PostController.php
├── config/blog.php
├── database/migrations/2026_01_01_000000_create_posts_table.php
├── resources/views/index.blade.php
├── routes/web.php
├── lang/pt/messages.php
└── tests/
```

`composer.json`:

```json
{
  "name": "acme/blog",
  "type": "library",
  "require": { "php": "^8.5", "slendie/framework": "^4.0" },
  "autoload": { "psr-4": { "Acme\\Blog\\": "src/" } },
  "extra": { "slendie": { "providers": ["Acme\\Blog\\BlogServiceProvider"] } }
}
```

```php
final class BlogServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/blog.php', 'blog');
        $this->app->singleton(PostRepository::class);
    }

    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');                      // grupo web por defeito
        $this->loadRoutesFrom(__DIR__.'/../routes/api.php', ['prefix' => 'api', 'middleware' => 'api']);
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'blog');              // view('blog::index')
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'blog');                  // __('blog::messages.hi')

        $this->router()->aliasMiddleware('blog.author', EnsureAuthor::class);
        $this->router()->pushMiddlewareToGroup('web', TrackVisits::class);
        Blade::directive('excerpt', fn ($e) => "<?php echo Str::limit($e, 120); ?>");
        Blade::if('author', fn () => auth()->user()?->is_author);

        $this->commands([InstallCommand::class, PublishPostCommand::class]);

        $this->publishes([__DIR__.'/../config/blog.php' => config_path('blog.php')], 'blog-config');
        $this->publishes([__DIR__.'/../resources/views' => resource_path('views/vendor/blog')], 'blog-views');
        $this->publishesMigrations([__DIR__.'/../database/migrations' => database_path('migrations')], 'blog-migrations');
    }
}
```

Depois de `composer require acme/blog` o provider é **descoberto automaticamente** (via `vendor/composer/installed.json`;
cache em `bootstrap/cache/packages.php`, regenerada no `post-autoload-dump`). Nada a editar no projecto.

## O que o projecto pode sobrepor

| Recurso do package | Override no projecto |
|---|---|
| Views `blog::x` | `resources/views/vendor/blog/x.blade.php` |
| Traduções `blog::m.k` | `lang/vendor/blog/pt/m.php` |
| Config `blog.*` | `config/blog.php` (publicado) |
| Descoberta | `composer.json` → `extra.slendie.dont-discover: ["acme/blog"]` |

## Providers adiados

```php
protected bool $defer = true;
public function provides(): array { return [PostRepository::class]; }
```

Só regista quando alguém pede `PostRepository`.

## Publicar no Packagist

1. Repositório Git público com `composer.json` válido e tag `v1.0.0`.
2. Submeter em packagist.org → "Submit".
3. Activar o webhook do GitHub para actualizações automáticas.

## Package de referência

`slendie/auth` usa todos os mecanismos acima: rotas, views com namespace, migrações, traduções, middlewares, directivas
`@auth/@guest`, comandos (`auth:install`, `make:user`), `publishes`. Começa por copiar a sua estrutura.
