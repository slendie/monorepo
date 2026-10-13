# slendie/auth

Autenticação para a [Slendie](https://slendie.lcloss.dev): login por sessão com *remember me*, tokens de API com abilities,
middlewares `auth` / `guest` / `ability`, directivas `@auth` / `@guest` e comandos de instalação.

Serve também de **package de referência**: usa todas as capacidades de `ServiceProvider` (config, rotas, views, migrações,
traduções, middlewares, directivas Blade, comandos, `publishes`). Veja `src/AuthServiceProvider.php`.

## Instalação

```bash
composer require slendie/auth
php slendie auth:install
```

O `auth:install` publica `config/auth.php`, as migrações e `app/Models/User.php`, corre `migrate`, oferece criar o primeiro
utilizador (`php slendie make:user`) e diz onde entrar: **`/login`**. Precisa de `APP_KEY` (`php slendie key:generate`) para o *remember me*.

## Em dois minutos

```php
// routes/web.php
Route::prefix('/admin')->middleware('auth')->group(function (): void {
    Route::get('/', fn () => view('admin.index'));
});

// routes/api.php  (era 'api.token' no skeleton)
Route::middleware('auth:api')->group(function (): void {
    Route::apiResource('leads', LeadController::class, ['except' => ['store']]);
});
```

```bash
php slendie make:user --name=Ana --email=ana@x.pt        # pergunta a password (sem eco)
php slendie auth:token ana@x.pt --abilities=leads.read   # imprime o token uma única vez
curl -H "Authorization: Bearer <token>" http://localhost:8000/api/leads
```

```blade
@auth Olá, {{ auth()->user()->name }} @endauth
@guest <a href="{{ route('login') }}">Entrar</a> @endguest
```

## O que traz

| | |
|---|---|
| Guards | `web` (sessão + *remember me*) e `api` (Bearer token) — `auth()->guard('api')` |
| Rotas | `GET/POST /login`, `POST /logout` (nome `login` / `logout`) |
| Middlewares | `auth`, `auth:api`, `guest`, `ability:leads.write` |
| Model | `Slendie\Auth\Authenticatable` + `HasApiTokens` (publica-se um `App\Models\User`) |
| Segurança | sessão e token CSRF regenerados no login; tokens e remember tokens só como sha256; cookie remember assinada com a `APP_KEY`; 5 tentativas/min por email+IP; rehash automático |
| Publicável | `auth-config`, `auth-migrations`, `auth-views`, `auth-lang`, `auth-model` (`php slendie vendor:publish --tag=…`) |
| Testes | `$this->actingAs($user)` e `$this->actingAs($user, 'api')` (Slendie\Testing) |

A documentação completa está em [`docs/auth.md`](../../docs/auth.md).

## Desenvolvimento

No monorepo (`packages/auth`): `composer test`, `composer analyse`, `composer lint` na raiz. Os testes usam uma app de fixture
em `tests/Fixtures/app` (SQLite em memória, sessão em array) e projectos temporários para `auth:install`.

## Fora de âmbito

Reset de password por email (o core não tem mail), verificação de email, 2FA, OAuth/socialite, policies/gates, `logoutOtherDevices`, vários providers.

## Licença

MIT.
