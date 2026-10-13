# core-04-12 — Package `slendie/auth`

> Peça 12 · Depende de: 09, 11 · Sessão única de IA · Pasta `packages/auth/`
> Referência obrigatória: `core-02-architecture.md` §2, §5 (Service Provider); decisão do utilizador: Auth fora do core

## Objectivo
Autenticação por sessão e por token para o painel, instalável com dois comandos, sem obrigar o core a conhecer
utilizadores. Serve de **package de referência**: usa todas as capacidades da peça 09 e a documentação de packages
aponta para ele.

## Estrutura (`packages/auth/`)
```
composer.json            # slendie/auth, require slendie/framework ^4.0, extra.slendie.providers
src/AuthServiceProvider.php
src/AuthManager.php      # guard(?name): Guard; extend(driver, Closure); user(), check(), guest(), id(), login(), logout(), attempt(), once(), viaRemember()
src/Contracts/{Guard,StatefulGuard,UserProvider,Authenticatable}.php
src/Guards/SessionGuard.php      # sessão + remember-me cookie (token hash em users.remember_token, assinado com APP_KEY)
src/Guards/TokenGuard.php        # Bearer token → tabela personal_access_tokens (hash sha256, abilities json, expires_at, last_used_at)
src/Providers/ModelUserProvider.php  # retrieveById/ByCredentials/ByToken, validateCredentials (password_verify)
src/Authenticatable.php          # trait para o model User: getAuthIdentifierName/getAuthIdentifier/getAuthPassword/getRememberToken/setRememberToken
src/HasApiTokens.php             # trait: createToken(name, abilities, expiresAt): NewAccessToken; tokens(); currentAccessToken(); tokenCan()
src/Models/{User,PersonalAccessToken}.php    # User publicável para app/Models/User.php
src/Middleware/{Authenticate,RedirectIfAuthenticated,EnsureTokenCan}.php   # aliases auth, guest, ability
src/Http/Controllers/{LoginController,LogoutController,PasswordResetController?}.php  # reset fora de âmbito (sem mail no core) → não
src/Http/Requests/LoginRequest.php           # throttle simples por sessão+IP em cache de ficheiro (5 tentativas / 1 min)
src/Console/{InstallCommand,MakeUserCommand,TokenCreateCommand}.php
src/Hashing/Hasher.php            # password_hash/verify (bcrypt default, argon2id opcional via config), needsRehash
src/Events? não
src/helpers.php                   # auth(?guard), bcrypt()
config/auth.php                   # defaults.guard web; guards web(session, provider users), api(token); providers users(model App\Models\User); passwords não
database/migrations/0001_01_01_000000_create_users_table.php      # id, name, email unique, password, remember_token, timestamps
database/migrations/0001_01_01_000001_create_personal_access_tokens_table.php
resources/views/layouts/auth.blade.php  login.blade.php             # namespace auth::; publicáveis
routes/web.php                    # GET /login (guest), POST /login, POST /logout (auth)
lang/{pt,en}/auth.php             # failed, throttle, password
stubs/User.stub
tests/                            # Pest, app de fixture
README.md
```

## Comportamentos
- `AuthServiceProvider`: `mergeConfigFrom`, `loadRoutesFrom` (grupo `web`), `loadViewsFrom('auth')`, `loadMigrationsFrom`, `loadTranslationsFrom`, aliases de middleware `auth`, `guest`, `ability:`, `Blade::if('auth')` e `Blade::if('guest')` (gera `@auth/@endauth`, `@guest/@endguest`), `Request::setUserResolver(fn ($guard) => auth($guard)->user())`, `commands`, `publishes` (config, views, migrations, User model stub → `app/Models/User.php`), regista `auth` no container (para `actingAs` do Testing)
- `auth:install`: publica config + migrations + User model (se não existir), acrescenta `->middleware('auth')` sugerido no output, corre `migrate`, pergunta se quer criar o primeiro utilizador (`make:user`), imprime URL `/login`
- `make:user {--name=} {--email=} {--password=}` interactivo (secret), valida email único
- `auth:token {email} {--name=cli} {--abilities=*}` cria token e imprime uma vez
- `SessionGuard::attempt(credentials, remember)`: `retrieveByCredentials` (excepto `password`), `validateCredentials`, `login()` (regenera sessão e token CSRF), remember cookie `slendie_remember` (`id|token|hash`) com 400 dias, `HttpOnly`, `SameSite=Lax`; `logout()` limpa sessão, cookie e roda `remember_token`; `logoutOtherDevices` **não**
- `TokenGuard`: `bearerToken()` → `hash('sha256')` lookup, verifica `expires_at`, actualiza `last_used_at` (throttle 1/min), `tokenCan(ability)`
- `Authenticate` middleware: guest → `expectsJson` ? 401 JSON : redirect `route('login')` guardando `url.intended`; `RedirectIfAuthenticated`: auth → redirect `config('auth.home', '/admin')`
- `LoginController`: `show()`, `store(LoginRequest)` → `attempt` → `redirect()->intended(config('auth.home'))`; falha → `back()->withErrors(['email' => __('auth::auth.failed')])->onlyInput('email')`; throttle → 429 com segundos
- Views: `auth::login` com layout próprio minimalista (`auth::layouts.auth`) que usa `public/css/app.css` do skeleton se existir; `@error`, `old()`, checkbox remember, CSRF
- `Hasher`: `make`, `check`, `needsRehash`, `info`; config `hashing.driver bcrypt|argon2id`, `bcrypt.rounds 12`
- Rehash automático em `attempt` se `needsRehash`
- `Testing`: `actingAs($user, 'web'|'api')` funciona via `SessionGuard::setUser`/`TokenGuard::setUser`; `assertAuthenticatedAs`
- Integração com skeleton: README explica substituir `api.token` por `auth:api` em `routes/api.php` e proteger `/admin` com `auth`

## Testes (Pest, app fixture com skeleton mínimo)
Login válido/inválido/throttle; remember-me recria sessão a partir do cookie; logout invalida cookie; middleware `auth` redirecciona e guarda intended; `guest` redirecciona autenticado; `@auth/@guest` compilam e avaliam; `TokenGuard` com token válido/expirado/sem ability; `createToken` devolve plaintext uma vez e guarda hash; `auth:install` publica e migra numa pasta temporária; `make:user` cria com hash bcrypt; `actingAs` em ambos os guards; `Hasher` bcrypt/argon2id; `ModelUserProvider` com `User` customizado (`config('auth.providers.users.model')`); views publicadas sobrepõem as do package; `lang/vendor/auth/pt/auth.php` sobrepõe mensagens.

## Critérios de aceitação
```
composer require slendie/auth
php slendie auth:install
```
→ `/login` funcional; rota `/admin` com `->middleware('auth')` protegida; `php slendie auth:token admin@x.pt` dá token que abre `/api/leads` com `auth:api`. `composer test/analyse/lint` verdes no monorepo; `docs/auth.md` e `packages/auth/README.md` escritos.

## Fora de âmbito
Reset de password por email (depende de mail), verificação de email, 2FA, OAuth/socialite, policies/gates (talvez `slendie/authz` depois), múltiplos providers.

## Implementação
- `packages/auth` completo: `AuthManager`, `SessionGuard`, `TokenGuard`, `ModelUserProvider`, `Hasher`, traits `Authenticatable` e
  `HasApiTokens`, models `User` e `PersonalAccessToken`, middlewares `auth`/`guest`/`ability`, `LoginController`/`LogoutController`/
  `LoginRequest`, comandos `auth:install`/`make:user`/`auth:token`, views, traduções pt/en, migrações, stub `User`, helpers e config.
- Testes (Pest, 89): login/logout/throttle/intended, remember-me (incl. cookies forjadas e rotação), tokens (válido, expirado,
  abilities, `last_used_at`), `actingAs`, directivas Blade, `Hasher` (bcrypt/argon2id), model customizado, `auth:install` /
  `make:user` / `auth:token` em projectos temporários, overrides de views e traduções. `composer test/analyse/lint` verdes.
- Documentação: `docs/auth.md`, `packages/auth/README.md`, `CHANGELOG.md`.

## Desvios
- **Middleware global `AuthenticationContext`** (não previsto): os guards não têm acesso à resposta, por isso as cookies remember-me ficam
  em fila no guard e este middleware junta-as à resposta; também regista o resolver de `Request::user()` (o `Request::user()` do core não
  recebe o guard, devolve o do guard activo: o último que autenticou via `auth:xxx`, senão `auth.defaults.guard`).
- **`redirect()->intended()`**: o core não tem `intended()`; o package regista `AuthRedirector` (subclasse de `Redirector`) no binding
  `Redirector`/`redirect`. Fica nomeado como `url.intended` na sessão (guardado só para pedidos GET).
- **Throttle por email+IP** em vez de sessão+IP (a sessão descarta-se com um cookie novo). Ficheiros JSON em
  `storage/framework/auth-throttle/{sha1}.json` (`Support\LoginThrottle`); janela fixa a contar da primeira falha. Excede → `HttpException(429)`
  com `Retry-After` e a mensagem `auth::auth.throttle`.
- **Remember token**: `users.remember_token` guarda o sha256 (e não o valor da cookie); só existe um por utilizador, logo um novo login com
  "lembrar-me" invalida a cookie do dispositivo anterior. O logout roda sempre o token e envia a cookie expirada.
- **Estado por pedido**: os guards são singletons mas esquecem o utilizador quando o `Request` muda (`Guards\BindsToRequest`), excepto o fixado
  por `setUser()`/`actingAs()` (e o `login()` sem sessão, p.ex. na consola). Sem isto, nos testes um pedido herdava o login do anterior.
- **`auth` em pedidos sem sessão** (grupo `api`) responde `401` JSON mesmo sem `Accept: application/json`; com sessão, redirecciona.
- **`ability` e sessão**: utilizadores autenticados por sessão (sem token) passam em `ability:` (acesso total do painel); `tokenCan()` no model é
  estrito (falso sem token).
- **Tabela `personal_access_tokens`** usa `user_id` (sem *morph*): um só tipo de utilizador por aplicação. Token em claro: `{id}|{segredo}`; o
  `TokenGuard` aceita também só o segredo.
- **Migrações** com `Schema::hasTable()` no `up()`: existem no package (`loadMigrationsFrom`) e publicadas; sem o guard, `migrate` falharia por
  tabela duplicada. `auth:install` publica com `--tag auth-config,auth-migrations,auth-model`; `--no-migrate` e `--force` extra.
- `AuthManager::provider()` público (usado por `make:user` e `auth:token`); `auth()` sem argumentos devolve o `AuthManager`.
- O cast `'password' => 'hashed'` da documentação inicial não existe no `Model` do core: o hash faz-se no mutator do trait `Authenticatable`
  (`setPasswordAttribute`); `docs/auth.md` actualizado.
- `LoginController` devolve `withInput(['email' => …])` em vez de `onlyInput('email')` (o `RedirectResponse` do core não tem `onlyInput`).
- `PasswordResetController` e `Events` não foram criados (fora de âmbito, como na spec).
- Monorepo: `slendie/auth` entra em `require-dev` do `composer.json` raiz e `Slendie\Auth\Tests\` em `autoload-dev`.
