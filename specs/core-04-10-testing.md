# core-04-10 — Testing (utilitários para projectos) e cobertura do core

> Peça 10 · Depende de: 01–09 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Testes), §6 (Qualidade)

## Objectivo
Quem cria um projecto Slendie escreve testes Pest como no Laravel: `$this->get('/')->assertOk()->assertSee('x')`,
`RefreshDatabase`, `actingAs` (gancho para o package auth), fakes de sessão. Além disso, fechar a cobertura do core em
≥ 80% e passar PHPStan nível 8 sem baseline.

## Ficheiros a criar (`framework/src/Testing/`)
- `TestCase.php extends PHPUnit\Framework\TestCase` (Pest usa-a via `uses()`)
  - `createApplication(): Application` — por defeito `require base_path('bootstrap/app.php')`; o projecto pode sobrepor; `$this->app`
  - `setUp`: `Facade::clearResolvedInstances()`, `Facade::setFacadeApplication`, cria app, `APP_ENV=testing`, corre `setUpTraits()` (detecta `RefreshDatabase`, `WithoutMiddleware`, `DatabaseTransactions`, `WithFaker` **não**)
  - `tearDown`: `Model::flushEventListeners()`, fecha connections, `Container::setInstance(null)`, `$_SESSION` limpo
  - `afterApplicationCreated(Closure)`, `beforeApplicationDestroyed(Closure)`
- `Concerns/MakesHttpRequests.php` (trait no TestCase)
  - `get($uri, $headers = [])`, `getJson`, `post($uri, $data = [], $headers = [])`, `postJson`, `put/putJson/patch/patchJson/delete/deleteJson/options/head`, `json($method, $uri, $data, $headers)`, `call($method, $uri, $parameters, $cookies, $files, $server, $content)`
  - `withHeaders()`, `withHeader()`, `withToken(token, type = 'Bearer')`, `withCookie()`, `withCookies()`, `withSession(array)`, `flushSession()`, `withServerVariables()`, `withoutMiddleware(?class|array)`, `withMiddleware()`, `followingRedirects()`, `withUnencryptedCookies` **não**, `from(url)` (define referer/previous url para `back()`), `withCredentials` **não**
  - constrói `Request::create()`; sessão em memória (`ArraySessionHandler`) partilhada entre pedidos do mesmo teste; CSRF: `withSession(['_token' => 'x'])` + `_token` automático em `post()` quando `withoutCsrf()`? **decisão**: como no Laravel, `VerifyCsrfToken` é **desactivado automaticamente em ambiente `testing`** (`$app->environment('testing')` → middleware faz `$next`); documentar; `withCsrf()` para testar explicitamente
  - devolve `TestResponse`
- `TestResponse.php` — envolve `Response`:
  - status: `assertStatus(int)`, `assertOk`, `assertCreated`, `assertNoContent`, `assertNotFound`, `assertForbidden`, `assertUnauthorized`, `assertUnprocessable`, `assertMethodNotAllowed`, `assertServerError`, `assertSuccessful`, `assertRedirect(?uri)`, `assertRedirectToRoute(name, params)`, `assertRedirectContains`
  - headers: `assertHeader(name, ?value)`, `assertHeaderMissing`, `assertLocation`, `assertCookie(name, ?value)`, `assertCookieMissing`, `assertPlainCookie`
  - body: `assertSee(string|array, escape = true)`, `assertSeeInOrder`, `assertSeeText` (strip tags), `assertDontSee`, `assertDontSeeText`, `assertContent(string)`
  - view: `assertViewIs(name)`, `assertViewHas(key, ?value|Closure)`, `assertViewHasAll`, `assertViewMissing`, `viewData(key)`; (requer `ViewResponse` expor `View` original)
  - json: `assertJson(array, strict = false)` (subset), `assertExactJson`, `assertJsonFragment`, `assertJsonMissing`, `assertJsonPath(path, value|Closure)`, `assertJsonCount(n, ?path)`, `assertJsonStructure(array)`, `assertJsonValidationErrors(array|string, key = 'errors')`, `assertJsonMissingValidationErrors`, `assertJsonIsArray/Object`, `json(?path)`, `collect()`? não, `decodeResponseJson()`
  - sessão: `assertSessionHas(key, ?value)`, `assertSessionHasAll`, `assertSessionMissing`, `assertSessionHasErrors(array|string, ?format, bag)`, `assertSessionHasNoErrors`, `assertSessionDoesntHaveErrors`, `assertSessionHasInput(key, ?value)`, `assertValid(keys)`, `assertInvalid(keys|array of key=>message)`
  - utilitários: `getContent()`, `status()`, `headers()`, `dump()`, `dd()`, `dumpHeaders()`, `dumpSession()`, `baseResponse`, `__get` para `$response->content`
  - mensagens de falha úteis: ao falhar `assertStatus` incluir excepção capturada (se houver) e primeiros 500 chars do body; ao falhar `assertSee`, mostrar excerto
- `Concerns/InteractsWithDatabase.php` — `assertDatabaseHas(table, array $data, ?connection)`, `assertDatabaseMissing`, `assertDatabaseCount(table, n)`, `assertDatabaseEmpty`, `assertModelExists(Model)`, `assertModelMissing`, `assertSoftDeleted(Model|table, data)`, `assertNotSoftDeleted`, `seed(?class)`, `castAsJson` **não**
- `RefreshDatabase.php` (trait) — antes de cada teste: se BD é SQLite `:memory:`, `migrate:fresh` uma vez por processo e depois cada teste corre dentro de transacção com rollback (rápido); para ficheiro/MySQL/pgsql: `migrate:fresh` na primeira vez do processo (`static $migrated`) + transacção por teste; `protected bool $seed = false`, `protected string $seeder`
- `DatabaseTransactions.php` (trait) — só a transacção, sem migrar
- `DatabaseMigrations.php` (trait) — migra e faz rollback real por teste (lento, para quem precisa)
- `WithoutMiddleware.php` (trait), `WithoutExceptionHandling` via `$this->withoutExceptionHandling()` (re-lança a excepção no teste em vez de renderizar) e `withExceptionHandling()`; `assertThrows(Closure, class, ?message)`
- `Concerns/InteractsWithSession.php` — `withSession`, `session()`, `flushSession`, `startSession`
- `Concerns/InteractsWithContainer.php` — `swap(abstract, instance)`, `instance()`, `mock(class, Closure)` **não** (sem Mockery; documentar uso de `swap` com classes anónimas), `partialMock` não, `spy` não, `forgetMock` não
- `Concerns/InteractsWithAuthentication.php` — **ganchos genéricos**: `actingAs(object $user, ?string $guard = null)` chama `$this->app->make('auth')->guard($guard)->setUser($user)` **se** `auth` existir no container, senão lança `LogicException('Instale slendie/auth')`; `assertAuthenticated`, `assertGuest`, `assertAuthenticatedAs`; `be()` alias
- `Concerns/InteractsWithConsole.php` — `artisan`? **nome**: `$this->console('migrate', ['--force' => true])` devolve `PendingCommand` com `assertExitCode`, `assertSuccessful`, `assertFailed`, `expectsOutput(string)`, `doesntExpectOutput`, `expectsOutputToContain`, `expectsTable`, `expectsQuestion(q, answer)`, `expectsConfirmation(q, 'yes'|'no')`, `expectsChoice`, `run()`; implementa via `BufferedOutput` e STDIN simulado
- `Fakes/`: `ArraySessionHandler`, `FakeUploadedFile::create(name, kb)` / `::image(name, w, h)` (gera PNG real pequeno com GD se disponível, senão bytes PNG mínimos), `TestResponse` já; `Mail`/`Queue` fakes não existem (sem esses serviços)
- `Pest` integração: `framework/src/Testing/Pest.php`? Não — o skeleton traz `tests/Pest.php` com `uses(Tests\TestCase::class)->in('Feature')` e `tests/TestCase.php extends Slendie\Testing\TestCase`; `expect()` extensões: `toBeRedirect`, `toBeOk`? **não** adicionar (manter PHPUnit asserts via TestResponse; Pest `expect` nativo cobre o resto)
- `phpunit.xml` do skeleton: `APP_ENV=testing`, `DB_CONNECTION=sqlite`, `DB_DATABASE=:memory:`, `SESSION_DRIVER=array`, `APP_KEY` fixo
- `Session/ArraySessionHandler` como driver `array` real em `config/session.php` (`SESSION_DRIVER=array`) — usado em testes

## Cobertura e qualidade do core (segunda metade da peça)
- Correr `pest --coverage --min=80` (requer Xdebug ou PCOV em CI; adicionar ao `ci.yml` com `pcov`); listar ficheiros < 60% e completar testes das peças anteriores onde faltar.
- `phpstan analyse --level=8` sem `ignoreErrors` e sem baseline; corrigir tipos.
- `pint --test` limpo.
- Benchmark informativo em `framework/tests/bench/` (não corre no CI): 1000 pedidos a `/` com view simples; registar `req/s` e memória em `docs/performance.md` com condições (PHP 8.5 CLI, SQLite memória, Windows/Linux).

## Testes (Pest, sobre o próprio Testing)
- `TestCase` arranca app do fixture; `get('/')->assertOk()->assertSee()`; `postJson` 422 → `assertJsonValidationErrors`; `assertRedirect` + `assertSessionHasErrors` após POST inválido; `followingRedirects`; `withSession`; `withoutMiddleware`; `withoutExceptionHandling` re-lança; `assertViewIs/Has`; `assertDatabaseHas` + `RefreshDatabase` isola testes (inserir num teste, não existe no seguinte); `assertDatabaseCount`; `assertSoftDeleted`; `console('migrate:status')->expectsOutputToContain()->assertSuccessful()`; `expectsQuestion`; `FakeUploadedFile` em `post()` chega como `UploadedFile` válido; `actingAs` sem auth lança `LogicException` claro; mensagens de falha incluem body excerpt.

## Critérios de aceitação
- Skeleton: `composer test` corre `tests/Feature/HomeTest.php` e `ContactTest.php` (com `RefreshDatabase`) em < 2 s.
- Cobertura do core ≥ 80%, PHPStan 8 limpo, Pint limpo; CI verde com SQLite + MySQL + PostgreSQL.
- `docs/testing.md` completo (incl. "Diferenças para o Laravel": sem Mockery/fakes de Mail/Queue/Event, CSRF desligado em testing, `console()` em vez de `artisan()`).

## Desvios
- **Sessão em memória:** o `ArraySessionHandler` já existia em `Session/` (peça 02) e o driver `array` já estava em `config/session.php`;
  não há `Testing/Fakes/ArraySessionHandler`. O `TestCase` instala um handler novo em `session.handler` por teste e o cliente de teste
  guarda a cookie de sessão (jar de cookies) entre pedidos.
- **CSRF desligado por marca, não por ambiente:** `VerifyCsrfToken` ganhou um construtor opcional `?Application` e deixa passar quando o container
  tem `testing.csrf.disabled` (definido pelo `TestCase`; `withCsrf()` retira-o). O plano "desligar em `APP_ENV=testing`" partiria os testes de CSRF da
  própria framework, que correm com `APP_ENV=testing` (phpunit.xml) e exercitam o CSRF a sério.
- **Pequenas alterações ao core, só para os testes:** `Pipeline` ignora os middlewares listados em `testing.middleware.disabled`
  (`withoutMiddleware`); o `Kernel` relança a excepção se existir `testing.exceptions.throw` (`withoutExceptionHandling` — o `Kernel` engolia
  o que o `Handler` relançasse); `DatabaseManager::setConnection()` (partilhar a ligação já migrada entre aplicações).
- **`RefreshDatabase` não declara propriedades** (`$seed`, `$seeder`, `$connectionsToTransact`): um trait que as declare impede a classe de as
  sobrepor com outro valor (erro fatal). Lêem-se com `property_exists`; o estado partilhado entre testes (ligações migradas) vive em
  `Testing\DatabaseState`, porque propriedades estáticas de um trait seriam uma cópia por classe de teste (um `migrate:fresh` por ficheiro Pest).
  A chave inclui a configuração da ligação, o `basePath` e o semeador. Traços comuns em `Concerns\ManagesTestDatabase`.
- **Nomes:** `Model::clearBootedModels()` (já existente) faz o papel de "flushEventListeners"; `InteractsWithExceptionHandling` (novo trait) concentra
  `withoutExceptionHandling/withExceptionHandling/assertThrows`; `InteractsWithSession` expõe também `session($key)` e `startSession()`;
  `csrfToken()` devolve o token da sessão do cliente.
- **`TestResponse`:** a sessão é um retrato tirado no fim do pedido (as asserções de sessão não mudam se o teste fizer outro pedido depois);
  `RecordingHandler` embrulha o `Handler` da aplicação para o `TestResponse` mostrar a excepção nas falhas; `assertJsonPath` usa `assertEquals`;
  o `Location` é comparado com e sem o host e ignorando a barra final. `assertJsonStructure` segue a semântica do Laravel (`'*'` para listas).
- **`FakeUploadedFile`** estende `UploadedFile`; `create()` escreve a assinatura do formato (PDF/PNG/JPEG/GIF/ZIP) para o MIME detectado pelo
  conteúdo coincidir com o declarado, e `createWithContent()` é novo. Os `UploadedFile` entram em `post()` como em `multipart` (também aninhados).
- **`PendingCommand`** corre no primeiro `assert*()`/`run()` ou no destrutor, para uma expectativa nunca ficar por verificar. As respostas
  das perguntas são consumidas pela ordem de declaração; as perguntas não acabam em quebra de linha, por isso `expectsOutput` (linha exacta)
  não serve para a saída que vem logo depois de uma pergunta (use `expectsOutputToContain`).
- **Pest da framework:** o `beforeEach` global (`tests/Pest.php`, `framework/tests/Pest.php`) passa a ignorar testes que estendam
  `Slendie\Testing\TestCase` (que gerem o seu próprio estado global); sem isso apagava a aplicação acabada de criar.
- **Fixture:** `framework/tests/_data/testing` (projecto mínimo com rotas, migração, views) + `Fixtures/Testing/*` (`FixtureTestCase`, controller, seeder,
  `Fails` para verificar asserções negativas).

### Qualidade e cobertura (resultado)
- Cobertura do core: **93,5 %** (`pest --coverage --min=80`). Ficheiros que estavam abaixo de 60 % e foram completados: `ResponseFactory`, `Controller`,
  `Relation`, `Support\Facades\Response`/`URL`, `View\...\Misc` (`@dd/@dump/@inject/@php`), `HandleExceptions`, `Testing\WithoutMiddleware`; o `ServeCommand`
  passou a ter o caminho do processo coberto (arranca `php -S` sobre uma pasta `public/` inexistente, que sai logo com erro). Resta por testar o
  servidor a correr durante tempo indeterminado.
- **PHPStan nível 8 sem baseline e sem `ignoreErrors`:** o antigo `ignoreErrors` (`trait.unused` dos traits opcionais) foi substituído por
  `framework/phpstan/OptInTraits.php`, uma classe só para a análise que "usa" `HasUuids`, `SoftDeletes` e os traits de teste (assim também são analisados).
- Pint limpo. CI: o job SQLite corre `composer test:coverage` (PCOV); MySQL e PostgreSQL correm `composer test`. **Não verificado localmente:** os jobs
  MySQL/PostgreSQL (não há serviços aqui; os 45 testes que exigem essas bases ficam *skipped* sem DSN).
- Benchmark em `framework/tests/bench/requests.php` + `docs/performance.md` (≈ 850 req/s, 1,2 ms/pedido, Windows 11, PHP 8.5 CLI, Xdebug desligado; sem medição em Linux).
- Skeleton verificado num projecto temporário com `composer install` (framework por *symlink*): `HomeTest` + `ContactTest` = 8 testes em ≈ 0,8 s.