# Package `slendie/auth`

Autenticação por sessão (painel) e por token (API). Não está no core porque nem todo o site precisa de login.

```bash
composer require slendie/auth
php slendie auth:install         # publica config/auth.php, migrações e app/Models/User.php; migra; oferece criar utilizador
php slendie make:user
```

Rotas adicionadas: `GET /login` (nome `login`), `POST /login` e `POST /logout` (nome `logout`).

## Proteger rotas

```php
Route::prefix('/admin')->middleware('auth')->group(function () { ... });
Route::get('/login', ...)->middleware('guest');

// API por token
Route::middleware('auth:api')->group(function () { Route::apiResource('/leads', ...); });
Route::middleware(['auth:api', 'ability:leads.write'])->patch('/leads/{lead}', ...);
```

| Alias | Faz |
|---|---|
| `auth` / `auth:api` / `auth:web,api` | deixa passar quem está autenticado num dos guards. Visitante: pedido que espera JSON **ou sem sessão** (rotas `api`) → `401 {"message":"Unauthenticated."}`; caso contrário redirecciona para a rota `login` e guarda o URL em `url.intended` |
| `guest` | quem já está autenticado vai para `config('auth.home')` |
| `ability:a,b` | o token do pedido tem de ter **todas** as abilities (`403` se não). Utilizadores de sessão (sem token) passam: têm acesso total, como o próprio painel |

Depois de passar em `auth:api`, `$request->user()` devolve o utilizador desse guard (o guard activo é o último que autenticou).

## Usar

```php
auth()->check();  auth()->guest();  auth()->user();  auth()->id();
auth()->attempt(['email' => $e, 'password' => $p], remember: true);
auth()->login($user);  auth()->logout();  auth()->once($credentials);  auth()->loginUsingId(1);
auth('api')->user();
$request->user();
return redirect()->intended('/admin');   // volta ao URL pretendido antes do login
```

`auth()` sem argumentos devolve o `AuthManager`; com o nome de um guard devolve esse guard. `attempt/login/logout/once/viaRemember`
só existem em guards de sessão (num guard `token` lançam `LogicException`).

```blade
@auth Olá, {{ auth()->user()->name }} @endauth
@guest <a href="{{ route('login') }}">Entrar</a> @endguest
@auth('api') ... @endauth
```

## Model `User`

`php slendie auth:install` publica `app/Models/User.php`. Sem ele, o package usa `Slendie\Auth\Models\User`.

```php
final class User extends Model implements Authenticatable
{
    use \Slendie\Auth\Authenticatable, \Slendie\Auth\HasApiTokens;
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];
}
```

O trait `Authenticatable` faz o hash do `password` ao atribuí-lo (`User::create(['password' => 'segredo'])`); um hash já feito não é refeito.
Para outro model ou outra tabela: `config('auth.providers.users.model')` (ou `AUTH_MODEL` no `.env`). O model tem de estender
`Slendie\Model\Model` e implementar `Slendie\Auth\Contracts\Authenticatable`; a chave primária pode ter qualquer nome.

## Tokens de API

```php
$token = $user->createToken('react-admin', ['leads.read', 'leads.write'], expiresAt: null);
$token->plainTextToken;             // "{id}|{segredo}": mostra uma vez; na BD fica só o sha256 do segredo
$user->tokens();  $user->currentAccessToken();  $user->tokenCan('leads.write');
```

```bash
php slendie auth:token ana@x.pt --name=cli --abilities=leads.read,leads.write     # sem --abilities = "*"
curl -H "Authorization: Bearer <token>" http://localhost:8000/api/leads
```

`tokenCan()` é verdade só para o utilizador que se autenticou *com* um token (`currentAccessToken()`). Tokens expirados (`expires_at`)
são recusados; `last_used_at` actualiza-se no máximo uma vez por minuto.

## Login

Formulário em `auth::login` (publicável com `vendor:publish --tag=auth-views`; usa o `public/css/app.css` do projecto se existir).
O `LoginController` valida o formulário, aplica o limitador, faz `attempt()` e vai para `redirect()->intended(config('auth.home'))`.
Falhas voltam ao formulário com `Estas credenciais não correspondem…` e o email antigo (a mesma mensagem para email desconhecido).

- **Throttle:** 5 falhas por **email + IP** numa janela de 60 s (ficheiros em `storage/framework/auth-throttle`, nomes sha1: o email não fica em claro).
  À quinta falha, o pedido seguinte responde `429` com `Retry-After`. Um login bem sucedido limpa o contador. (Chave por email+IP e não por
  sessão: quem descarta a cookie de sessão não foge ao limite.)
- **Sessão:** ao autenticar, o id da sessão e o token CSRF são regenerados (anti *session fixation*); o logout invalida a sessão.
- **Remember me:** cookie `slendie_remember` com `id|token|hmac` (HttpOnly, SameSite=Lax, `Secure` em HTTPS, 400 dias). O `token` (60 caracteres
  aleatórios) só existe na cookie; em `users.remember_token` fica o seu **sha256** e o `hmac` (sha256 com a `APP_KEY`) impede cookies forjadas.
  Precisa de `APP_KEY` (`php slendie key:generate`). Há um token por utilizador: um novo login com "lembrar-me" invalida a cookie do
  dispositivo anterior, e o logout invalida todas.
- **Rehash:** se o custo do bcrypt (ou o algoritmo) mudou, o hash é refeito no próximo login bem sucedido.
- **Quem junta as cookies à resposta:** o guard não vê a resposta, por isso o package regista o middleware global `AuthenticationContext`, que
  também liga `$request->user()` ao guard activo.

## Configuração (`config/auth.php`)

| Chave | Por omissão |
|---|---|
| `defaults.guard` | `web` |
| `guards.web` / `guards.api` | `session` / `token`, provider `users` |
| `providers.users.model` | `App\Models\User` (`AUTH_MODEL`) |
| `home` | `/admin` |
| `remember.cookie` / `remember.days` | `slendie_remember` / `400` |
| `throttle.max_attempts` / `throttle.decay_seconds` | `5` / `60` |
| `hashing.driver` | `bcrypt` (`HASH_DRIVER`) ou `argon2id` |
| `hashing.bcrypt.rounds` | `12` (`BCRYPT_ROUNDS`) |

O merge com a config do package é raso (1.º nível): se definir `guards` ou `hashing`, defina-os por inteiro.

`php slendie vendor:publish` lista as tags: `auth-config`, `auth-migrations`, `auth-views`, `auth-lang`, `auth-model`.
Views publicadas em `resources/views/vendor/auth/` e traduções em `lang/vendor/auth/{pt,en}/auth.php` sobrepõem as do package.

## Estender

```php
// guard próprio: fn (Application $app, string $name, array $config, UserProvider $provider): Guard
app('auth')->extend('meu-driver', fn ($app, $name, $config, $provider) => new MeuGuard(...));
// config/auth.php → 'guards' => ['x' => ['driver' => 'meu-driver', 'provider' => 'users']]
```

## Skeleton: da API com token fixo para `auth:api`

O skeleton protege `/api/leads` com `api.token` (um `API_TOKEN` fixo). Depois de instalar o package:

```php
// routes/api.php
Route::middleware('auth:api')->group(function (): void {
    Route::apiResource('leads', LeadController::class, ['except' => ['store']]);
});

// routes/web.php
Route::prefix('/admin')->middleware('auth')->group(function (): void { /* ... */ });
```

```bash
php slendie auth:token admin@x.pt            # copie o token
curl -H "Authorization: Bearer <token>" http://localhost:8000/api/leads
```

## Testes

```php
$this->actingAs($user)->get('/admin')->assertOk();            // guard web
$this->actingAs($user, 'api')->getJson('/api/leads')->assertOk();
$this->assertAuthenticated();  $this->assertGuest();  $this->assertAuthenticatedAs($user);
$this->withToken($user->createToken('t')->plainTextToken)->getJson('/api/leads');
```

`actingAs()` fixa o utilizador no guard durante todo o teste; sem factories: `User::create([...])`. No fim de um pedido, o estado
de login de um pedido não vaza para o seguinte (excepto o definido por `actingAs()`).

## Notas de implementação

- As migrações existem no package (`loadMigrationsFrom`, para `migrate` funcionar logo após `composer require`) e são publicáveis
  (`auth:install`); por isso cada `up()` começa por `Schema::hasTable()`: correr as duas não falha.
- `redirect()->intended()` vem do `AuthRedirector`, que o package regista no lugar do `Redirector` (subclasse sem outras diferenças).

## Fora de âmbito
Reset de password por email, verificação de email, 2FA, OAuth, policies/gates, `logoutOtherDevices`, vários providers.
