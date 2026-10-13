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

### Descoberta, cache e exclusões

- `php slendie package:discover` lê `vendor/composer/installed.json` e escreve `bootstrap/cache/packages.php`
  (lista "Discovered Package: acme/blog"). O skeleton corre-o no `post-autoload-dump`, por isso `composer require` basta.
- Sem cache, a descoberta faz-se em runtime (custa ler um JSON). `php slendie about` mostra os packages descobertos.
- `extra.slendie.aliases` (`{"Blog": "Acme\Blog\Facade"}`) cria `class_alias` no arranque.
- Excluir: no `composer.json` do **projecto**, `"extra": {"slendie": {"dont-discover": ["acme/blog"]}}` (ou `["*"]` para todos).
  Depois de mudar, volta a correr `package:discover`.
- Ordem de registo: providers da framework → packages descobertos → `bootstrap/providers.php` → `config('app.providers')`.

### Helpers do `ServiceProvider`

| Método | O que faz |
|---|---|
| `mergeConfigFrom($path, 'blog')` | defaults do package; o projecto vence. **Merge raso** (1.º nível, como no Laravel): se o projecto define `blog.options`, substitui o array inteiro |
| `loadRoutesFrom($path, $attrs = ['middleware' => 'web'])` | carrega as rotas num grupo, depois do arranque |
| `loadViewsFrom($path, 'blog')` | `view('blog::x')`; `resources/views/vendor/blog` do projecto tem prioridade |
| `loadMigrationsFrom($paths)` | `migrate` e restantes comandos passam a ver estas pastas |
| `loadTranslationsFrom($path, 'blog')` | `__('blog::m.k')`; override em `lang/vendor/blog/{locale}` |
| `publishes($map, $tags)` | `vendor:publish --tag=...` copia (nunca substitui sem `--force`) |
| `publishesMigrations($map, $tags)` | como `publishes`, mas o timestamp de cada migração passa a "agora" (e republicar não duplica) |
| `commands([...])` | comandos de consola (só em CLI) |
| `callAfterResolving(X::class, fn ($x) => ...)` | corre quando `X` é criado (ou já, se existir); ideal para directivas Blade |
| `$this->router()` / `$this->blade()` | atalhos para o `Router` e o `BladeCompiler` |

`Blade::if('admin', fn () => ...)` cria `@admin … @elseadmin … @else … @endadmin` e passa os argumentos da directiva
(`@role('editor')` → `fn (string $name)`).

## O que o projecto pode sobrepor

| Recurso do package | Override no projecto |
|---|---|
| Views `blog::x` | `resources/views/vendor/blog/x.blade.php` |
| Traduções `blog::m.k` | `lang/vendor/blog/pt/m.php` |
| Config `blog.*` | `config/blog.php` (publicado) |
| Descoberta | `composer.json` → `extra.slendie.dont-discover: ["acme/blog"]` |

## Registar comandos

Um package traz os seus comandos de consola registando-os no `boot()` do provider. Só têm efeito em CLI (`php slendie`);
numa página web `commands()` não faz nada.

```php
public function boot(): void
{
    $this->commands([InstallCommand::class, PublishPostCommand::class]);
}
```

```php
final class InstallCommand extends Command
{
    protected string $signature = 'blog:install {--force : Substitui ficheiros existentes}';
    protected string $description = 'Instala o blog (config, migrações e views)';

    public function handle(): int
    {
        $this->call('vendor:publish', ['--tag' => ['blog-config', 'blog-migrations'], '--force' => $this->option('force')]);
        $this->call('migrate');

        return self::SUCCESS;
    }
}
```

- Os comandos são instanciados pelo container: dependências no construtor ou, de preferência, no `handle()`.
- Aparecem em `php slendie list` (agrupados pelo prefixo antes de `:`) e em `php slendie help blog:install`.
- `$this->publishes([...], 'tag')` torna ficheiros publicáveis com `php slendie vendor:publish --tag=tag`; sem argumentos o
  comando lista as tags disponíveis. `--provider=Classe` publica tudo o que um provider registou e `--all` tudo.
- Para scripts rápidos usa `Console::command('blog:ping', fn () => $this->info('pong'))` num ficheiro carregado pelo provider.

## Providers adiados

```php
protected bool $defer = true;
public function provides(): array { return [PostRepository::class]; }
```

Só regista quando alguém pede `PostRepository` (ou quando se declara à mão com `$app->addDeferredServices([...])`).

## Publicar no Packagist

1. Repositório Git público com `composer.json` válido e tag `v1.0.0`.
2. Submeter em packagist.org → "Submit".
3. Activar o webhook do GitHub para actualizações automáticas.

## Package de referência

`slendie/auth` usa todos os mecanismos acima: rotas, views com namespace, migrações, traduções, middlewares, directivas
`@auth/@guest`, comandos (`auth:install`, `make:user`), `publishes`. Começa por copiar a sua estrutura.
