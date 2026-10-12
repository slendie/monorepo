# core-04-02 — HTTP e Routing

> Peça 02 · Depende de: 01 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §4, §5 (Rotas, Controllers, Middleware), §6

## Objectivo
Pedido HTTP entra em `public/index.php`, passa pelo Kernel e pipeline de middlewares, chega a um controller via
Router com parâmetros e DI, e sai como `Response`. 404/405 e página de debug funcionam. Nada faz `echo` fora de `Response::send()`.

## Ficheiros a criar (`framework/src/`)

### Http
- `Http/HttpMethod.php` — enum string: GET, HEAD, POST, PUT, PATCH, DELETE, OPTIONS; `fromString()` (case-insensitive)
- `Http/Request.php`
  - `static capture(): self` (a partir de superglobais); `static create(string $uri, string $method = 'GET', array $parameters = [], array $cookies = [], array $files = [], array $server = [], ?string $content = null)` (para testes)
  - `method(): HttpMethod` — já com spoofing: se POST e `_method` em input ou header `X-HTTP-Method-Override` ∈ {PUT, PATCH, DELETE}, usa-o; `realMethod()`
  - `path()` normalizado (sem trailing slash excepto `/`, sem query), `uri()`, `fullUrl()`, `url()`, `root()`, `scheme()`, `host()`, `isSecure()`, `ip()`, `userAgent()`
  - `query(?string $key = null, mixed $default = null)`, `post()`, `input()` (query + body + json merged; body vence), `all()`, `only()`, `except()`, `has()`, `filled()`, `boolean()`, `integer()`, `string()`
  - `json(?string $key = null, mixed $default = null)` — decode lazy de `php://input` quando `Content-Type: application/json`
  - `file(string $key): ?UploadedFile`, `hasFile()`, `allFiles()`
  - `header(string $key, ?string $default = null)`, `headers(): array`, `bearerToken(): ?string`, `cookie()`, `cookies()`
  - `isMethod()`, `isJson()`, `wantsJson()`, `expectsJson()` (Accept json ou XHR ou `/api/*`), `ajax()`, `isPrefetch()`
  - `route(?string $param = null)`: a `Route` matched ou um parâmetro dela; `setRoute()`, `routeParameters()`
  - `session(): Session` (lança `RuntimeException` se sessão não iniciada), `setSession()`, `hasSession()`
  - `validate(array $rules)` fica `// TODO(core-04-08)`
  - `user()` fica como atributo genérico: `setUserResolver(Closure)`, `user()`; o package auth preenche
  - `attributes` bag: `attribute(key, default)`, `setAttribute()` (para middlewares passarem dados)
- `Http/UploadedFile.php` — `clientName()`, `extension()`, `mimeType()`, `size()`, `isValid()`, `path()`, `store(string $dir, ?string $name = null): string` (move para `storage/app/{dir}`), `storePublicly()` (para `public/uploads`)
- `Http/Response.php` — `__construct(string $content = '', int $status = 200, array $headers = [])`; `setContent/getContent`, `setStatusCode/getStatusCode`, `header(name, value)`, `withHeaders()`, `headers()`, `cookie(Cookie|string $name, ...)`, `send(): void` (status, headers, cookies, body; `HEAD` → sem body), `isOk()`, `isRedirect()`, `isSuccessful()`; `__toString`
- `Http/JsonResponse.php extends Response` — `__construct(mixed $data, int $status = 200, array $headers = [], int $flags = JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)`; `getData()`, `setData()`
- `Http/RedirectResponse.php extends Response` — `__construct(string $url, int $status = 302)`; `with(key, value)` (flash para sessão), `withInput(?array)`, `withErrors()` (guarda em flash `errors`; a MessageBag vem na peça 08, aqui aceita array), `getTargetUrl()`
- `Http/ViewResponse.php extends Response` — recebe `Slendie\View\View` (interface `Renderable` definida aqui em `Contracts/Renderable.php`); renderiza lazy em `getContent()`/`send()`. A implementação real da View é da peça 03; aqui basta a interface
- `Http/Cookie.php` — value object: name, value, expires, path, domain, secure, httpOnly, sameSite; `toHeaderString()`
- `Http/Kernel.php`
  - `__construct(Application $app, Router $router)`
  - `protected array $middleware = [TrimStrings::class, SecurityHeaders::class]` (globais)
  - `protected array $middlewareGroups = ['web' => [StartSession::class, VerifyCsrfToken::class, ShareErrorsFromSession::class], 'api' => [HandleCors::class]]`
  - `protected array $middlewareAliases = ['csrf' => VerifyCsrfToken::class, 'cors' => HandleCors::class, 'throttle' => ...` (throttle fica fora; não listar)
  - `handle(Request): Response` — bootstrap da app, pipeline global → `$router->dispatch($request)`; `try/catch Throwable` → `Handler::render()`
  - `terminate(Request, Response)` — chama `terminate()` em middlewares que o tenham (ex.: gravar sessão)
  - métodos `pushMiddleware`, `prependMiddleware`, `appendMiddlewareToGroup`, `aliasMiddleware` (usados por providers/packages)
  - A app regista `Kernel` no container; `bootstrap/app.php` do skeleton pode estender para alterar listas
- `Http/Controller.php` (abstract) — vazio excepto `middleware(string|array $m)` para declarar middleware por controller (lido pelo Router ao despachar) e helpers protegidos `view()`, `redirect()`, `json()`, `response()` que delegam para os helpers globais
- `Http/Middleware/StartSession.php` — inicia `Session` com config `session.php`; coloca em `Request::setSession()`; `terminate()` grava; flash lifecycle (age flash data)
- `Http/Middleware/VerifyCsrfToken.php` — para métodos não seguros (POST/PUT/PATCH/DELETE): compara `_token` do input ou header `X-CSRF-TOKEN`/`X-XSRF-TOKEN` com `session()->token()` via `hash_equals`; falha → `TokenMismatchException` (419); `protected array $except = []` (padrões de path com `*`)
- `Http/Middleware/TrimStrings.php` — trim recursivo de inputs string; converte `''` em `null` apenas se `config('app.convert_empty_strings_to_null', true)`; excepções `password`, `password_confirmation`
- `Http/Middleware/SecurityHeaders.php` — `X-Content-Type-Options: nosniff`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy: strict-origin-when-cross-origin`, `Permissions-Policy` básica; configurável em `config('app.security_headers')`
- `Http/Middleware/HandleCors.php` — a partir de `config('cors.php')` (paths, allowed_origins, allowed_methods, allowed_headers, exposed_headers, max_age, supports_credentials); responde preflight OPTIONS com 204
- `Http/Middleware/ShareErrorsFromSession.php` — partilha `$errors` com as views (chama `View\Factory::share` se existir; senão guarda em `Request::setAttribute('errors')`)
- `Http/Middleware/SubstituteBindings.php` — route-model binding; nesta peça só resolve parâmetros tipados com classes que implementem `Contracts/UrlRoutable` (`resolveRouteBinding(mixed $value, ?string $field): ?static`); o `Model` implementa na peça 05

### Routing
- `Routing/Route.php` — `methods: HttpMethod[]`, `uri`, `action` (Closure | [class, method] | 'Class@method' | string invokable), `name()`, `middleware()`, `withoutMiddleware()`, `where(array|string, ?string)`, `whereNumber/whereAlpha/whereAlphaNumeric/whereUuid/whereIn`, `defaults()`, `prefix`, `domain` (fora de âmbito: ignorar), `parameters()`, `parameter(name)`, `setParameter()`, `compile(): CompiledRoute` (regex com `{param}`, `{param?}` opcional, constraints), `matches(Request): bool`, `getName()`, `uri()`, `getActionName()`
- `Routing/RouteCollection.php` — `add(Route)`, `match(Request): Route` (lança `NotFoundHttpException` ou `MethodNotAllowedHttpException` com `Allow` header), `getByName()`, `getRoutes()`, `hasNamedRoute()`, índice por método para performance; HEAD cai em GET; OPTIONS automático se não houver rota: responde 204 com `Allow`
- `Routing/Router.php`
  - `get/post/put/patch/delete/options/any/match(array $methods, ...)`, `view(uri, view, data = [])`, `redirect(uri, destination, status = 302)`, `permanentRedirect()`, `fallback(action)`
  - `resource(name, controller, options = ['only'|'except'])` → 7 rotas nomeadas (`posts.index`, …) com parâmetro `{post}`; `apiResource()` (sem create/edit)
  - `group(array $attributes, Closure|string $routes)`; fluente: `prefix()`, `middleware()`, `name()`, `namespace()`(não), `controller(Class)` (acções passam a ser strings de método) → devolvem `RouteGroup` com `group()`
  - `aliasMiddleware(alias, class)`, `middlewareGroup(name, array)`, `getMiddleware()`; `pushMiddlewareToGroup`
  - `dispatch(Request): Response` — `match` → `request->setRoute()` → junta middleware (grupo da route + aliases + `Controller::middleware()`) → `Pipeline` → `runRoute()` → `toResponse(mixed)`
  - `toResponse(mixed $result): Response` — `Response` passa; `Renderable` → `ViewResponse`; `array|JsonSerializable|Arrayable` → `JsonResponse`; `string|Stringable` → `Response`; `null` → `Response('', 204)`? **não**: `null` → `Response('')` 200 (menos surpresa); outro → `UnexpectedValueException`
  - `runRoute()` — `Container::call()` do action com `routeParameters` + DI; closures também
  - `loadRoutes(string $file)` — `require` com `Route` facade disponível; chamado pelo provider de rotas
  - `current(): ?Route`, `currentRouteName()`, `has(name)`, `is(pattern)`
- `Routing/RouteGroup.php` — acumula atributos e aplica a cada `Route` criada no interior (prefix concatena com `/` normalizado; name concatena; middleware merge)
- `Routing/Pipeline.php` — `send($passable)->through(array $pipes)->then(Closure $destination)`; pipes: class-string (resolve pelo container, chama `handle($passable, $next)`), closures, instâncias, strings `alias:param1,param2` (parâmetros extra passados a `handle` após `$next`)
- `Routing/UrlGenerator.php` — `to(path, params, secure)`, `route(name, params, absolute = true)` (substitui `{param}`, extra → query string; falta de parâmetro obrigatório → `UrlGenerationException`), `asset(path)`, `current()`, `previous(fallback)`, `full()`; base URL de `config('app.url')` ou do Request
- `Routing/Route.php` (facade) em `Routing/Facades/Route.php`? **Não**: colocar facade em `Slendie\Routing\Route`? Conflito com a classe `Route`. Decisão: classe de rota é `Slendie\Routing\Route`; facade é `Slendie\Support\Facades\Route`. Idem `Slendie\Support\Facades\{URL, Response}`
- `Routing/RoutingServiceProvider.php` — regista `Router`, `UrlGenerator`, `RouteCollection` como singletons; `boot()`: carrega `routes/web.php` (grupo `web`) e `routes/api.php` (prefix `/api`, grupo `api`) se existirem em `basePath('routes')`
- `Contracts/UrlRoutable.php`, `Contracts/Renderable.php`, `Contracts/Arrayable.php`

### Session
- `Session/Session.php` — `start()`, `isStarted()`, `id()`, `regenerate(bool $destroy = false)`, `invalidate()`, `get/put/has/exists/forget/flush/pull/all`, `push(key, value)`, `increment()`, `flash(key, value)`, `now()`, `reflash()`, `keep()`, `getOldInput(key)`, `flashInput(array)`, `token()`, `regenerateToken()`, `previousUrl()/setPreviousUrl()`; ageing de flash em `save()`
- `Session/FileSessionHandler.php implements SessionHandlerInterface` — ficheiros em `storage/framework/sessions`, `gc()` por lifetime
- `Session/SessionServiceProvider.php` — lê `config/session.php` (`driver: file`, `lifetime: 120`, `cookie`, `path`, `domain`, `secure: null→auto`, `http_only: true`, `same_site: lax`, `files`), configura `session_set_save_handler`, `session_name`, `session_set_cookie_params`

### Foundation / Exceptions
- `Exceptions/HttpException.php` (status, headers), `NotFoundHttpException` (404), `MethodNotAllowedHttpException` (405, `Allow`), `TokenMismatchException` (419), `UrlGenerationException`
- `Foundation/Exceptions/Handler.php`
  - `report(Throwable)` → `Logger` (ficheiro `storage/logs/slendie.log`, formato `[Y-m-d H:i:s] env.LEVEL: message {context}` + trace); `protected array $dontReport = [HttpException::class, ValidationException::class]`
  - `render(Request, Throwable): Response` — `HttpException` → se `expectsJson` → `JsonResponse(['message' => ...], status)`; senão view `errors.{status}` se existir, senão `errors.generic`; `Throwable` genérico → debug (`APP_DEBUG=true`): página HTML com classe, mensagem, ficheiro:linha, excerto de código ±10 linhas, stack trace, dados do request, env sanitizado (`*KEY*`, `*SECRET*`, `*PASSWORD*`, `*TOKEN*` → `••••`); produção → `errors.500` / JSON `{message: 'Server Error'}`
  - `renderForConsole(Throwable)` texto simples com trace
  - Páginas em `framework/resources/views/errors/{404,403,419,500,generic,debug}.blade.php` — **nesta peça** são templates PHP puros (`.php`) renderizados com `extract` + `include`, pois o Blade só chega na peça 03; a peça 03 converte-os
- `Foundation/Application.php` — implementar `handle(Request): Response` (delegar para `Kernel`) e `terminate()`; registar `RoutingServiceProvider`, `SessionServiceProvider` na lista de providers da framework; `Request` como singleton `'request'` actualizado a cada `handle`
- `Support/Facades/{Route,URL,Response,Session}.php`
- `Support/helpers.php` — acrescentar: `request(?key)`, `response(content='', status=200, headers=[])` (sem args devolve `ResponseFactory` com `json()/redirect()/view()/noContent()/download()`), `redirect(?to, status=302)` (sem args devolve `Redirector` com `route()/back()/to()`), `back()`, `route(name, params=[], absolute=true)`, `url(?path)`, `asset(path)`, `session(?key, default)`, `old(key, default)`, `csrf_token()`, `csrf_field()` (string html), `method_field(method)`, `abort(code, message='', headers=[])`, `abort_if()`, `abort_unless()`, `cookie(...)`
- `Routing/Redirector.php`, `Http/ResponseFactory.php`

### Skeleton (apenas o necessário para correr; o resto é da peça 11)
- `skeleton/public/index.php`:
  ```php
  <?php declare(strict_types=1);
  require __DIR__.'/../vendor/autoload.php';
  $app = require __DIR__.'/../bootstrap/app.php';
  $response = $app->handle($request = Slendie\Http\Request::capture());
  $response->send();
  $app->terminate($request, $response);
  ```
- `skeleton/bootstrap/app.php` — `return (new Application(dirname(__DIR__)))->withRouting(web: __DIR__.'/../routes/web.php', api: __DIR__.'/../routes/api.php')->withMiddleware(fn (Kernel $k) => ...)->withExceptions(fn (Handler $h) => ...)->create();` — estilo Laravel 11. Implementar `Application::configure()`/`withRouting/withMiddleware/withExceptions/create` em `Foundation/Configuration/*` (ApplicationBuilder, Middleware, Exceptions) — pequenas classes de configuração fluente
- `skeleton/routes/web.php` com `Route::get('/', fn () => 'Slendie v4')`
- `skeleton/config/{app,session,cors}.php`

## Testes (Pest, `framework/tests/`)
- Unit `Routing/RouteTest`: compila `/posts/{id}`, `/posts/{id?}`, constraints `whereNumber`, match/no-match.
- Unit `Routing/RouteCollectionTest`: 404 vs 405 com `Allow`; HEAD→GET; OPTIONS automático.
- Unit `Routing/PipelineTest`: ordem de execução (onion), short-circuit, pipe com parâmetros `alias:a,b`.
- Unit `Http/RequestTest`: `create()` + spoofing `_method`; header override; `input()` precedence; `json()`; `expectsJson()`; `path()` normalização.
- Unit `Http/ResponseTest`: headers, status, cookies para header string, JSON flags, redirect url.
- Unit `Session/SessionTest` (handler em array para testes): flash ageing, token, old input.
- Feature `Routing/RouterTest` (Application em fixture): closure route; controller `[Class, 'method']` com DI no construtor e parâmetro de rota; `resource()` gera 7 rotas com nomes; grupos com prefix/name/middleware; `fallback`; `redirect()`; `view()` (com fake Renderable); `toResponse` para string/array/Response/null.
- Feature `Http/KernelTest`: middleware global corre; CSRF 419 em POST sem token e passa com token; `TrimStrings`; `SecurityHeaders` presentes; CORS preflight 204.
- Feature `Exceptions/HandlerTest`: 404 html vs json; debug page contém classe da excepção; produção esconde; env sanitizado.

## Critérios de aceitação
- `php -S localhost:8000 -t skeleton/public` responde `Slendie v4` em `/`, 404 em `/x`, 405 em `POST /`.
- Formulário com `@csrf`-equivalente (`csrf_field()`) passa; sem token → 419.
- `composer test/analyse/lint` verdes.
- Documentação: `docs/routing.md`, `docs/controllers.md`, `docs/requests-responses.md`, `docs/middleware.md`, `docs/sessions.md`, `docs/errors.md` actualizados com a API real.

## Não fazer nesta peça
Blade (peça 03), validação (08), auth (package), rate limiting (fora), domínio em rotas (fora).

## Desvios
(preencher na implementação)
