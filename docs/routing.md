# Rotas

Rotas ficam em `routes/web.php` (grupo `web`: sessão + CSRF) e `routes/api.php` (prefixo `/api`, grupo `api`: CORS, sem sessão).
Os ficheiros são carregados pelo `RoutingServiceProvider`; para usar outros caminhos, em `bootstrap/app.php`:

```php
(new Application(dirname(__DIR__)))->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php')
```

## Básico

```php
use Slendie\Support\Facades\Route;
use App\Http\Controllers\HomeController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/ola', fn () => 'Olá');                        // string → Response
Route::get('/dados', fn () => ['ok' => true]);             // array → JSON
Route::view('/sobre', 'about', ['title' => 'Sobre']);      // view directa (motor de views: peça 03)
Route::redirect('/antigo', '/novo');                       // 302; Route::permanentRedirect() → 301
Route::get('/invoke', InvokableController::class);         // __invoke
Route::get('/legacy', 'App\Http\Controllers\X@show');      // Class@method também funciona
```

A acção pode ser: `Closure`, `[Classe::class, 'método']`, `'Classe@método'` ou o nome de uma classe invocável.

## Métodos HTTP

`Route::get`, `post`, `put`, `patch`, `delete`, `options`, `any`, `match(['get', 'post'], ...)`.

- `HEAD` é respondido automaticamente por qualquer rota `GET` (sem body).
- `OPTIONS` sem rota explícita responde `204` com `Allow` (e herda o middleware das rotas desse caminho, por isso o
  preflight de CORS funciona).
- Formulários HTML só enviam GET/POST: usa `method_field('PUT')` no formulário (campo `_method`) ou o header
  `X-HTTP-Method-Override`. Só `PUT`, `PATCH` e `DELETE` são aceites, e só em `POST`.
- Método errado para um caminho existente → `405` com header `Allow`.

## Parâmetros

```php
Route::get('/posts/{id}', [PostController::class, 'show']);
Route::get('/posts/{id}/{slug?}', ...);                     // opcional
Route::get('/users/{id}', ...)->whereNumber('id');
Route::get('/users/{name}', ...)->whereAlpha('name');       // também whereAlphaNumeric()
Route::get('/x/{uuid}', ...)->whereUuid('uuid');
Route::get('/x/{type}', ...)->whereIn('type', ['a', 'b']);
Route::get('/x/{code}', ...)->where('code', '[A-Z]{3}');
Route::get('/files/{path}', ...)->where('path', '.+');      // atravessa barras
Route::get('/invoice-{n}.pdf', ...);                        // parâmetro dentro de um segmento
```

No controller, o parâmetro chega pelo **nome** e é convertido para o tipo declarado (`int`, `float`, `bool`):

```php
public function show(int $id, Request $request) { ... }   // Request e outros serviços são injectados
```

`->defaults(['page' => 1])` define valores por omissão para parâmetros opcionais.

### Route-model binding

Parâmetros tipados com uma classe que implemente `Slendie\Contracts\UrlRoutable` são resolvidos pelo router; se
`resolveRouteBinding()` devolver `null`, o resultado é `404`.

```php
Route::get('/leads/{lead}', [LeadController::class, 'show']);
public function show(Lead $lead) { ... }                    // o nome do parâmetro tem de coincidir com {lead}
```

O `Model` (peça 05) implementa `UrlRoutable`. Binding por outra coluna (`{post:slug}`) fica para uma versão futura.

## Nomes e URLs

```php
Route::get('/leads/{lead}', ...)->name('leads.show');

route('leads.show', ['lead' => 5]);               // http://site/leads/5
route('leads.show', [5]);                         // posicional
route('leads.show', ['lead' => 5, 'tab' => 'x']); // /leads/5?tab=x (extras vão para a query string)
route('home', [], false);                         // relativo
url('/path');  url('a', [1, 2]);  asset('css/app.css');
url()->current();  url()->full();  url()->previous('/fallback');
```

Parâmetro obrigatório em falta ou nome inexistente → `UrlGenerationException`. A base é `config('app.url')`; sem ela
usa o host do pedido. Objectos com `getRouteKey()` são aceites como valor de parâmetro.

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

Route::group(['prefix' => 'api/v1', 'as' => 'v1.', 'middleware' => ['api']], function () { ... });
Route::group(['prefix' => 'admin'], base_path('routes/admin.php'));     // também aceita um ficheiro
```

Grupos aninhados concatenam `prefix` e `as` e juntam o `middleware`. Também funciona
`Route::prefix('x')->get('/y', ...)` (a rota nasce dentro do grupo).

## Resource

```php
Route::resource('posts', PostController::class);
Route::resource('posts', PostController::class, ['only' => ['index', 'show']]);
Route::resource('posts', PostController::class, ['except' => ['destroy']]);
Route::resource('posts', PostController::class, ['parameters' => ['posts' => 'article']]);   // {article}
Route::apiResource('leads', Api\LeadController::class);      // sem create/edit
Route::resource('posts.comments', CommentController::class); // /posts/{post}/comments/{comment}
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

`resource()` e `apiResource()` devolvem a lista de `Route` criadas.

## Fallback

```php
Route::fallback(fn () => response('Não encontrado', 404));
```

Só se aplica a pedidos `GET`/`HEAD` sem nenhuma rota correspondente (um `POST` num caminho inexistente continua a dar 404
pelo Handler, e um método errado continua a dar 405).

## Middleware em rotas

```php
Route::get('/x', ...)->middleware('auth');
Route::get('/x', ...)->middleware('auth', 'admin');         // ou ['auth', 'admin']
Route::get('/x', ...)->middleware(EnsureIsAdmin::class);
Route::get('/x', ...)->middleware('role:admin,editor');      // parâmetros após ':'
Route::get('/x', ...)->withoutMiddleware('csrf');            // alias, grupo ou classe
```

Não há rate limiting nesta versão (`throttle` não existe). Ver [middleware.md](middleware.md).

## Inspeccionar

```php
Route::current();  Route::currentRouteName();  Route::has('home');  Route::is('admin.*');
request()->route();            // a Route que fez match
request()->route('lead');      // um parâmetro
```

Na CLI: `php slendie route:list` (peça de consola).

## O que devolve um controller

| Devolve | Resposta |
|---|---|
| `Response` (ou subclasse) | tal e qual |
| `Renderable` (a `View` do motor de views) | HTML 200 (`ViewResponse`, renderizada dentro do pipeline) |
| `string`, `Stringable` | 200 com o texto |
| `array`, `JsonSerializable`, `Arrayable` | JSON 200 |
| `RedirectResponse` (`redirect(...)`) | 302 |
| `null` | 200 vazio |
| qualquer outra coisa | `UnexpectedValueException` |

## Como o pedido é despachado

`public/index.php` → `Application::handle()` → `Kernel` (middleware global) → `Router::dispatch()`:
`RouteCollection::match()` (404/405) → middleware da rota (grupo + controller + `SubstituteBindings`) → acção →
`toResponse()` → `Response::send()`.
