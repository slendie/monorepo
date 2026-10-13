# Testes

A Slendie usa [Pest](https://pestphp.com). O skeleton já traz `phpunit.xml`, `tests/Pest.php`, `tests/TestCase.php` e dois
testes de exemplo (`tests/Feature/HomeTest.php`, `ContactTest.php`). O `phpunit.xml` define `APP_ENV=testing`, SQLite em
memória (`DB_DATABASE=:memory:`), sessão `array` e uma `APP_KEY` fixa.

```bash
composer test                              # pest
./vendor/bin/pest --filter=Contact
./vendor/bin/pest --coverage --min=80      # precisa de Xdebug ou PCOV
```

## Como funciona

`Slendie\Testing\TestCase` (que estende a `PHPUnit\Framework\TestCase`) é a base dos testes da aplicação. Em cada teste:

1. cria uma `Application` nova a partir de `bootstrap/app.php` com `APP_ENV=testing` e arranca-a;
2. usa um handler de sessão **em memória**, partilhado entre os pedidos do mesmo teste (a "cookie" de sessão viaja sozinha,
   como num browser);
3. desliga a verificação CSRF (ver abaixo);
4. activa os traits de teste que a classe use (`RefreshDatabase`, ...);
5. no fim, deita tudo fora: aplicação, facades, eventos dos models, ligações e variáveis de ambiente.

O skeleton liga isto em `tests/Pest.php` (`pest()->extend(Tests\TestCase::class)->in('Feature')`). A `Tests\TestCase`
só diz onde está a aplicação:

```php
abstract class TestCase extends \Slendie\Testing\TestCase
{
    protected function createApplication(): Application
    {
        return require __DIR__.'/../bootstrap/app.php';
    }
}
```

Para uma base de dados limpa em cada teste, acrescente `RefreshDatabase` onde quiser (a um ficheiro, a uma pasta ou a
todos):

```php
use Slendie\Testing\RefreshDatabase;

uses(RefreshDatabase::class);                              // neste ficheiro
pest()->extend(Tests\TestCase::class)->use(RefreshDatabase::class)->in('Feature');   // em tests/Pest.php
```

## Testes HTTP

```php
it('mostra a home', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Bem-vindo')
        ->assertViewIs('home')
        ->assertViewHas('title');
});

it('grava um lead', function () {
    $this->post('/contact', ['name' => 'Ana', 'email' => 'ana@x.pt'])
        ->assertRedirect()
        ->assertSessionHas('status');

    $this->assertDatabaseHas('leads', ['email' => 'ana@x.pt']);
})->uses(RefreshDatabase::class);

it('valida', function () {
    $this->post('/contact', [])->assertRedirect()->assertSessionHasErrors(['name', 'email']);
    $this->postJson('/contact', [])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('api', function () {
    $this->withToken('abc')->getJson('/api/leads')
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonStructure(['data' => ['*' => ['id', 'name']]])
        ->assertJsonCount(3, 'data');
});
```

Métodos: `get`, `getJson`, `post`, `postJson`, `put`, `putJson`, `patch`, `patchJson`, `delete`, `deleteJson`, `options`, `head`,
`json($method, $uri, $data, $headers)` e `call($method, $uri, $parameters, $cookies, $files, $server, $content)`. Todos
devolvem um `TestResponse`. Os dados de `post()`/`put()`/... vão no corpo do formulário; as variantes `*Json` enviam JSON
(`Content-Type` e `Accept: application/json`).

Estado do cliente (vale para os pedidos seguintes do mesmo teste):

| Método | Efeito |
|---|---|
| `withHeaders([])`, `withHeader($k, $v)` | cabeçalhos |
| `withToken($token, $type = 'Bearer')` | `Authorization` |
| `withCookie($k, $v)`, `withCookies([])` | cookies enviadas (as da resposta guardam-se sozinhas) |
| `withSession([])` | grava chaves na sessão antes do pedido |
| `flushSession()` | esquece os dados da sessão |
| `session($key = null, $default = null)` | lê a sessão do cliente (sem argumentos, devolve um retrato `Session`) |
| `from('/url')` | de onde "vem" o utilizador: o `back()` volta a este URL |
| `followingRedirects()` | segue os redirects (até 20) com GET |
| `withServerVariables([])` | variáveis de `$_SERVER` (`REMOTE_ADDR`, ...) |
| `withoutMiddleware($classe = null)`, `withMiddleware()` | desliga (tudo, ou só a classe) / volta a ligar o middleware |
| `withoutExceptionHandling()`, `withExceptionHandling()` | relança a excepção original em vez da página de erro |
| `withCsrf()`, `csrfToken()` | liga a verificação CSRF / devolve o token da sessão |

### CSRF

O CSRF está **desligado** nos testes: a `TestCase` marca a aplicação e o `VerifyCsrfToken` deixa tudo passar. (Isto não
depende só de `APP_ENV=testing`, para que os testes da própria framework continuem a exercitar o middleware.) Para o
testar de propósito:

```php
$this->withCsrf()->post('/contact', $data)->assertStatus(419);
$this->withCsrf()->post('/contact', [...$data, '_token' => $this->csrfToken()])->assertRedirect();
```

### Excepções

```php
$this->get('/falha')->assertServerError();          // por omissão a excepção vira uma página de erro 500

$this->withoutExceptionHandling();                  // o teste vê a excepção verdadeira
expect(fn () => $this->get('/falha'))->toThrow(RuntimeException::class, 'mensagem');

$this->assertThrows(fn () => algo(), InvalidArgumentException::class, 'parte da mensagem');
```

Quando uma asserção de estado falha, a mensagem inclui a excepção que a causou (se a houve) e os primeiros 500 caracteres do
corpo — normalmente basta para perceber um 500 sem abrir os logs.

## Asserções da resposta

**Estado:** `assertOk`, `assertCreated`, `assertNoContent`, `assertNotFound`, `assertForbidden`, `assertUnauthorized`,
`assertUnprocessable`, `assertMethodNotAllowed`, `assertServerError`, `assertSuccessful`, `assertStatus(n)`,
`assertRedirect(?url)`, `assertRedirectToRoute(name, params)`, `assertRedirectContains(texto)`, `assertLocation(url)`.

**Cabeçalhos e cookies:** `assertHeader(nome, ?valor)`, `assertHeaderMissing`, `assertCookie(nome, ?valor)`,
`assertCookieMissing`, `assertPlainCookie` (igual a `assertCookie`: não há cookies encriptadas).

**Corpo:** `assertSee`, `assertSeeInOrder`, `assertSeeText` (sem tags), `assertDontSee`, `assertDontSeeText`, `assertContent`.
Por omissão o texto é escapado como o `{{ }}`; passe `escape: false` para procurar HTML cru.

**View:** `assertViewIs`, `assertViewHas(chave, ?valor|Closure)`, `assertViewHasAll`, `assertViewMissing`, `viewData(chave)`.

**JSON:** `assertJson` (subconjunto; `strict: true` compara tipos), `assertExactJson`, `assertJsonFragment`, `assertJsonMissing`,
`assertJsonPath(caminho, valor|Closure)`, `assertJsonCount(n, ?caminho)`, `assertJsonStructure` (`'*'` percorre listas),
`assertJsonValidationErrors`, `assertJsonMissingValidationErrors`, `assertJsonIsArray`, `assertJsonIsObject`, `json(?caminho)`,
`decodeResponseJson()`.

**Sessão:** `assertSessionHas(chave, ?valor|Closure)`, `assertSessionHasAll`, `assertSessionMissing`,
`assertSessionHasErrors(chaves, ?formato, bag)`, `assertSessionHasNoErrors`, `assertSessionDoesntHaveErrors`,
`assertSessionHasInput`, `assertValid(chaves)`, `assertInvalid(chaves)` (as duas últimas servem para sessão **e** JSON).
A sessão que estas asserções vêem é a do **fim do pedido** a que a resposta pertence.

**Depuração:** `dump()`, `dd()`, `dumpHeaders()`, `dumpSession()`; `$response->content`, `->status()`, `->headers()`,
`->exception()`.

## Base de dados

```php
uses(RefreshDatabase::class);

$this->assertDatabaseHas('leads', ['email' => 'ana@x.pt']);     // null = IS NULL
$this->assertDatabaseMissing('leads', [...]);
$this->assertDatabaseCount('leads', 3);   $this->assertDatabaseEmpty('leads');
$this->assertModelExists($lead);          $this->assertModelMissing($lead);
$this->assertSoftDeleted($lead);          $this->assertNotSoftDeleted('posts', ['id' => 1]);
$this->seed(LeadSeeder::class);           // sem argumento: Database\Seeders\DatabaseSeeder
```

Se o `assertDatabaseHas` falhar, a mensagem mostra quantas linhas a tabela tem e as três primeiras.

| Trait | O que faz | Quando |
|---|---|---|
| `RefreshDatabase` | `migrate:fresh` **uma vez por processo** e cada teste corre dentro de uma transacção desfeita no fim | por omissão; rápido, funciona com SQLite em memória, ficheiro, MySQL e PostgreSQL |
| `DatabaseTransactions` | só a transacção, sem migrar | a base de dados já está migrada |
| `DatabaseMigrations` | `migrate:fresh` antes e `migrate:reset` depois de cada teste | testar as próprias migrações (lento) |
| `WithoutMiddleware` | desliga todo o middleware em todos os testes da classe | raro |

O `RefreshDatabase` partilha a mesma ligação entre testes (é assim que a base em memória sobrevive). Opções — declare-as na
`TestCase` (ou numa classe de teste) com **exactamente** estes tipos:

```php
protected bool $seed = true;                       // corre o DatabaseSeeder depois do migrate:fresh
protected string $seeder = LeadSeeder::class;      // ou outro seeder
protected array $connectionsToTransact = [null, 'outra'];   // ligações a envolver numa transacção (null = a por omissão)
```

> A semente corre **uma vez por processo**, junto com as migrações, e o que ela escreve fica para todos os testes (só o
> que cada teste escreve é desfeito). Se um teste precisa de dados próprios, crie-os dentro do teste.

## Consola

`$this->console()` corre um comando e devolve um `PendingCommand` (o equivalente do `artisan()` do Laravel):

```php
$this->console('migrate:status')->assertSuccessful()->expectsOutputToContain('create_leads_table');

$this->console('make:user')
    ->expectsQuestion('Nome?', 'Ana')
    ->expectsConfirmation('Criar?', 'yes')
    ->expectsOutput('Utilizador criado.')
    ->assertExitCode(0);
```

Expectativas: `expectsOutput` (uma linha exacta), `doesntExpectOutput`, `expectsOutputToContain`, `doesntExpectOutputToContain`,
`expectsTable($headers, $rows)`, `expectsQuestion`, `expectsConfirmation`, `expectsChoice`. Finais: `assertExitCode`,
`assertNotExitCode`, `assertSuccessful` (`assertOk`), `assertFailed`; `output()` devolve o texto.

As respostas das perguntas são dadas **pela ordem** em que declara as expectativas. O comando corre no primeiro `assert*()`
(ou quando o objecto é destruído, para que uma expectativa nunca fique por verificar).

## Uploads

```php
use Slendie\Testing\Fakes\FakeUploadedFile;

$this->post('/perfil', ['avatar' => FakeUploadedFile::image('avatar.png', 100, 100)])->assertOk();
$this->post('/docs', ['ficheiro' => FakeUploadedFile::create('contrato.pdf', 200)]);   // 200 KB
$this->post('/import', ['csv' => FakeUploadedFile::createWithContent('x.csv', "a,b\n1,2\n")]);
```

São ficheiros reais numa pasta temporária (apagados no fim do processo): chegam ao controller como `UploadedFile` válido e
`store()` funciona. `image()` usa a extensão GD (PNG, JPEG ou GIF reais) e, sem GD, um PNG 1×1.

## Container

Não há Mockery. Substitua serviços por classes anónimas:

```php
$this->swap(Mailer::class, new class implements Mailer { public array $sent = []; public function send(...) { $this->sent[] = func_get_args(); } });
$this->instance(Clock::class, new FrozenClock('2026-01-01'));
```

`swap()` também limpa a instância já resolvida da facade correspondente.

## Autenticação (com `slendie/auth`)

A framework só traz os ganchos: `actingAs()` fala com o serviço `auth` registado pelo package e, sem ele, lança uma
`LogicException` a pedir `composer require slendie/auth`.

```php
$this->actingAs($user)->get('/admin')->assertOk();
$this->actingAs($user, 'api')->getJson('/api/leads');
$this->assertAuthenticated();  $this->assertGuest();  $this->assertAuthenticatedAs($user);
```

## Testes unitários

Classes puras (models, serviços) testam-se directamente com `expect()`. A `TestCase` da framework cria uma `Application` por
teste; para unitários puros estenda `PHPUnit\Framework\TestCase` (em `tests/Unit` o skeleton não aplica a `TestCase`).

## Cobertura e qualidade

```bash
composer test:coverage     # pest --coverage --min=80
composer analyse           # phpstan nível 8, sem baseline
composer lint              # pint --test
composer check             # lint + analyse + test
```

O CI corre tudo isto com SQLite, MySQL e PostgreSQL. A cobertura do core está acima de 90 % (mínimo exigido: 80 %).

## Diferenças para o Laravel

- Sem Mockery nem `mock()/spy()`: use `swap()` com classes anónimas.
- Sem fakes de `Mail`, `Queue`, `Event`, `Storage`, `Notification`, `Bus`.
- `console()` em vez de `artisan()`; não há `factory()` (use os models, `Model::create()` ou seeders).
- CSRF desligado nos testes por uma marca da `TestCase` (`withCsrf()` volta a ligá-lo).
- As cookies não são encriptadas (`assertPlainCookie` ≡ `assertCookie`).
- `assertJsonPath` compara com `assertEquals` (`1` e `'1'` são iguais).
- `RefreshDatabase` é configurado por propriedades da classe (`$seed`, `$seeder`, `$connectionsToTransact`) e não declara
  nenhuma no trait, para as poder sobrepor sem conflitos.
- Não há `WithFaker` nem `refreshApplication()`: cada teste já tem a sua aplicação.
