# Testes

A Slendie usa [Pest](https://pestphp.com). O skeleton já traz `tests/Pest.php`, `tests/TestCase.php` e dois testes
de exemplo. `phpunit.xml` define `APP_ENV=testing`, SQLite em memória e sessão `array`.

```bash
composer test
./vendor/bin/pest --filter=Contact
./vendor/bin/pest --coverage --min=80
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
    $this->post('/contacto', ['name' => 'Ana', 'email' => 'ana@x.pt', 'consent' => '1'])
        ->assertRedirect('/obrigado')
        ->assertSessionHas('success');

    $this->assertDatabaseHas('leads', ['email' => 'ana@x.pt']);
})->uses(RefreshDatabase::class);

it('valida', function () {
    $this->post('/contacto', [])->assertRedirect()->assertSessionHasErrors(['name', 'email']);
    $this->postJson('/contacto', [])->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('api', function () {
    $this->withToken('abc')->getJson('/api/leads')
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonStructure(['data' => [['id', 'name']]])
        ->assertJsonCount(3, 'data');
});
```

Métodos: `get`, `getJson`, `post`, `postJson`, `put`, `putJson`, `patch`, `patchJson`, `delete`, `deleteJson`,
`options`, `head`, `json($method, $uri, $data)`, `call(...)`.

Modificadores: `withHeaders([])`, `withHeader()`, `withToken()`, `withCookie()`, `withSession([])`, `from('/url')`,
`followingRedirects()`, `withoutMiddleware()`, `withoutExceptionHandling()`.

O CSRF está **desligado** em `APP_ENV=testing`. Para o testar explicitamente: `$this->withCsrf()`.

## Asserções da resposta

Status: `assertOk`, `assertCreated`, `assertNoContent`, `assertNotFound`, `assertForbidden`, `assertUnauthorized`,
`assertUnprocessable`, `assertStatus(n)`, `assertRedirect(?url)`, `assertRedirectToRoute(name)`.
Conteúdo: `assertSee`, `assertSeeText`, `assertDontSee`, `assertSeeInOrder`, `assertContent`.
Headers/cookies: `assertHeader`, `assertHeaderMissing`, `assertCookie`, `assertLocation`.
View: `assertViewIs`, `assertViewHas`, `assertViewMissing`.
JSON: `assertJson`, `assertExactJson`, `assertJsonFragment`, `assertJsonPath`, `assertJsonCount`, `assertJsonStructure`,
`assertJsonValidationErrors`, `assertJsonMissingValidationErrors`.
Sessão: `assertSessionHas`, `assertSessionMissing`, `assertSessionHasErrors`, `assertSessionHasNoErrors`,
`assertSessionHasInput`, `assertValid`, `assertInvalid`.
Debug: `dump()`, `dd()`, `dumpHeaders()`, `dumpSession()`.

## Base de dados

```php
uses(RefreshDatabase::class);                 // migra uma vez, transacção por teste

$this->assertDatabaseHas('leads', [...]);
$this->assertDatabaseMissing('leads', [...]);
$this->assertDatabaseCount('leads', 3);
$this->assertModelExists($lead);  $this->assertSoftDeleted($lead);
$this->seed(LeadSeeder::class);
```

Outros traits: `DatabaseTransactions` (só transacção), `DatabaseMigrations` (migra e reverte por teste).

## Consola

```php
$this->console('migrate:status')->assertSuccessful()->expectsOutputToContain('create_leads_table');
$this->console('make:user')->expectsQuestion('Nome?', 'Ana')->expectsConfirmation('Criar?', 'yes')->assertExitCode(0);
```

## Uploads

```php
$file = FakeUploadedFile::image('avatar.png', 100, 100);
$this->post('/perfil', ['avatar' => $file])->assertOk();
```

## Container

```php
$this->swap(Mailer::class, new class implements Mailer { ... });
$this->instance(Clock::class, new FrozenClock('2026-01-01'));
```

## Autenticação (com `slendie/auth`)

```php
$this->actingAs($user)->get('/admin')->assertOk();
$this->actingAs($user, 'api')->getJson('/api/leads');
$this->assertAuthenticated();  $this->assertGuest();
```

## Testes unitários

Classes puras (models, serviços) testam-se directamente com `expect()`. A `TestCase` da framework cria uma
`Application` por teste; para unitários puros estende `PHPUnit\Framework\TestCase`.

## Diferenças para o Laravel
Sem Mockery (usa `swap` com classes anónimas), sem fakes de Mail/Queue/Event/Storage, `console()` em vez de
`artisan()`, sem factories, CSRF desligado em testing.
