# Rotas

Rotas ficam em `routes/web.php` (sessão + CSRF) e `routes/api.php` (prefixo `/api`, JSON, CORS, sem sessão).

## Básico

```php
use Slendie\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ola', fn () => 'Olá');                        // string → Response
Route::get('/dados', fn () => ['ok' => true]);             // array → JSON
Route::view('/sobre', 'about', ['title' => 'Sobre']);      // view directa
Route::redirect('/antigo', '/novo', 301);
Route::get('/invoke', InvokableController::class);         // __invoke
Route::get('/legacy', 'App\Http\Controllers\X@show');      // Class@method também funciona
```

## Métodos HTTP

`Route::get`, `post`, `put`, `patch`, `delete`, `options`, `any`, `match(['get', 'post'], ...)`.

- `HEAD` é respondido automaticamente por qualquer rota `GET` (sem body).
- `OPTIONS` sem rota explícita responde `204` com `Allow`.
- Formulários HTML só enviam GET/POST: usa `@method('PUT')` na view (campo `_method`) ou o header `X-HTTP-Method-Override`.
- Método errado para um path existente → `405` com header `Allow`.

## Parâmetros

```php
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::get('/posts/{id}/{slug?}', ...);                     // opcional
Route::get('/users/{id}', ...)->whereNumber('id');
Route::get('/users/{name}', ...)->whereAlpha('name');
Route::get('/x/{uuid}', ...)->whereUuid('uuid');
Route::get('/x/{type}', ...)->whereIn('type', ['a', 'b']);
Route::get('/x/{code}', ...)->where('code', '[A-Z]{3}');
```

No controller, o parâmetro chega pelo **nome**:

```php
public function show(int $id, Request $request) { ... }   // Request e outros serviços são injectados
```

### Route-model binding

```php
Route::get('/leads/{lead}', [LeadController::class, 'show']);
public function show(Lead $lead) { ... }                    // Lead::find($id) ou 404
Route::get('/posts/{post:slug}', ...);                      // por outra coluna
```

## Nomes e URLs

```php
Route::get('/leads/{lead}', ...)->name('leads.show');

route('leads.show', ['lead' => 5]);              // /leads/5
route('leads.show', ['lead' => 5, 'tab' => 'x']); // /leads/5?tab=x
route('home', [], false);                         // relativo
url('/path');  asset('css/app.css');
```

Em Blade: `<a href="{{ route('home') }}">`.

## Grupos

```php
Route::prefix('/admin')->middleware('auth')->name('admin.')->group(function () {
    Route::get('/leads', [LeadController::class, 'index'])->name('leads.index');   // admin.leads.index
    Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);
});

Route::controller(LeadController::class)->group(function () {
    Route::get('/leads', 'index');
    Route::post('/leads', 'store');
});

Route::group(['prefix' => 'api/v1', 'middleware' => ['api']], function () { ... });
```

## Resource

```php
Route::resource('/posts', PostController::class);
Route::resource('/posts', PostController::class)->only(['index', 'show']);
Route::apiResource('/leads', Api\LeadController::class);      // sem create/edit
```

| Verbo | URI | Acção | Nome |
|---|---|---|---|
| GET | /posts | index | posts.index |
| GET | /posts/create | create | posts.create |
| POST | /posts | store | posts.store |
| GET | /posts/{post} | show | posts.show |
| GET | /posts/{post}/edit | edit | posts.edit |
| PUT/PATCH | /posts/{post} | update | posts.update |
| DELETE | /posts/{post} | destroy | posts.destroy |

## Fallback

```php
Route::fallback(fn () => response()->view('errors.404', [], 404));
```

## Middleware em rotas

```php
Route::get('/x', ...)->middleware('auth');
Route::get('/x', ...)->middleware(['auth', 'admin']);
Route::get('/x', ...)->middleware(EnsureIsAdmin::class);
Route::get('/x', ...)->middleware('throttle:10,1');          // parâmetros após ':'
Route::get('/x', ...)->withoutMiddleware(VerifyCsrfToken::class);
```

Ver [middleware.md](middleware.md).

## Inspeccionar

```bash
php slendie route:list
php slendie route:list --path=admin --method=GET --json
```

## O que devolve um controller

| Devolve | Resposta |
|---|---|
| `Response` (ou subclasse) | tal e qual |
| `View` (`view(...)`) | HTML 200 |
| `string` | HTML 200 |
| `array`, `JsonSerializable`, `Arrayable`, `Model`, `Collection`, `Paginator` | JSON 200 |
| `RedirectResponse` (`redirect(...)`) | 302 |
| `null` | 200 vazio |
