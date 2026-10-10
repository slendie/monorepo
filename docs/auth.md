# Package `slendie/auth`

Autenticação por sessão (painel) e por token (API). Não está no core porque nem todo o site precisa de login.

```bash
composer require slendie/auth
php slendie auth:install         # publica config/auth.php, migrações e app/Models/User.php; migra; cria utilizador
php slendie make:user
```

Rotas adicionadas: `GET /login`, `POST /login`, `POST /logout`.

## Proteger rotas

```php
Route::prefix('/admin')->middleware('auth')->group(function () { ... });
Route::get('/login', ...)->middleware('guest');

// API por token
Route::middleware('auth:api')->group(function () { Route::apiResource('/leads', ...); });
Route::middleware(['auth:api', 'ability:leads.write'])->patch('/leads/{lead}', ...);
```

## Usar

```php
auth()->check();  auth()->guest();  auth()->user();  auth()->id();
auth()->attempt(['email' => $e, 'password' => $p], remember: true);
auth()->login($user);  auth()->logout();
auth('api')->user();
$request->user();
```

```blade
@auth Olá, {{ auth()->user()->name }} @endauth
@guest <a href="{{ route('login') }}">Entrar</a> @endguest
```

## Model `User`

```php
final class User extends Model implements Authenticatable
{
    use \Slendie\Auth\Authenticatable, \Slendie\Auth\HasApiTokens;
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password', 'remember_token'];
    protected array $casts = ['password' => 'hashed'];
}
```

## Tokens de API

```php
$token = $user->createToken('react-admin', ['leads.read', 'leads.write'], expiresAt: null);
$token->plainTextToken;             // mostra uma vez; na BD fica o sha256
$user->tokens();  $user->currentAccessToken();  $user->tokenCan('leads.write');
```

```bash
php slendie auth:token ana@x.pt --name=cli --abilities=*
curl -H "Authorization: Bearer <token>" http://localhost:8000/api/leads
```

## Login

Formulário em `auth::login` (publicável com `vendor:publish --tag=auth-views`). *Throttle* de 5 tentativas por minuto
por sessão+IP. *Remember me* com cookie assinado de 400 dias. Rehash automático quando o custo do bcrypt muda.

`config/auth.php`: `defaults.guard`, `guards.web|api`, `providers.users.model`, `home` (redirect após login,
default `/admin`), `hashing.driver` (`bcrypt`|`argon2id`).

## Testes

```php
$this->actingAs(User::factory()...)   // sem factories: User::create([...])
$this->actingAs($user)->get('/admin')->assertOk();
$this->actingAs($user, 'api')->getJson('/api/leads')->assertOk();
```

## Fora de âmbito
Reset de password por email, verificação de email, 2FA, OAuth, policies/gates.
