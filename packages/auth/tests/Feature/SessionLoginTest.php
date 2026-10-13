<?php

declare(strict_types=1);

use Slendie\Auth\AuthManager;
use Slendie\Auth\Models\User;
use Slendie\Auth\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('mostra o formulário de login', function (): void {
    $this->get('/login')
        ->assertOk()
        ->assertSee('name="email"', false)
        ->assertSee('name="password"', false)
        ->assertSee('name="remember"', false)
        ->assertSee('name="_token"', false)
        ->assertSee('Log in');
});

it('faz login com credenciais válidas e redirecciona para config(auth.home)', function (): void {
    $this->makeUser();

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123'])
        ->assertRedirect('http://localhost/admin');

    $this->get('/admin')->assertOk()->assertSee('admin: Ana');
});

it('regenera o id da sessão e o token CSRF ao autenticar', function (): void {
    $this->makeUser();
    $session = $this->startSession();
    $oldId = $session->id();
    $oldToken = $session->token();
    $session->save();

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123'])->assertRedirect();

    $current = $this->session();

    expect($current->id())->not->toBe($oldId)
        ->and($current->token())->not->toBe($oldToken);
});

it('recusa credenciais inválidas e devolve o erro com o email antigo', function (): void {
    $this->makeUser();

    $this->from('/login')
        ->post('/login', ['email' => 'ana@x.pt', 'password' => 'errada'])
        ->assertRedirect('/login')
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.'])
        ->assertSessionHasInput('email', 'ana@x.pt');

    $this->get('/admin')->assertRedirect('http://localhost/login');
});

it('valida o formulário (email e password obrigatórios)', function (): void {
    $this->from('/login')
        ->post('/login', ['email' => 'nao-e-email'])
        ->assertSessionHasErrors(['email', 'password']);
});

it('não revela se o email existe (mesma mensagem para email desconhecido)', function (): void {
    $this->from('/login')
        ->post('/login', ['email' => 'ninguem@x.pt', 'password' => 'whatever-1'])
        ->assertSessionHasErrors(['email' => 'These credentials do not match our records.']);
});

it('bloqueia com 429 e Retry-After depois de 5 falhas (por email+IP)', function (): void {
    $this->makeUser();

    foreach (range(1, 5) as $_) {
        $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'errada'])->assertRedirect();
    }

    $response = $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);

    $response->assertStatus(429);
    expect((int) $response->baseResponse->getHeader('Retry-After'))->toBeGreaterThan(0)
        ->and($response->getContent())->toContain('Too many login attempts');

    // O bloqueio é por email: outro email não é afectado.
    $this->makeUser('bia@x.pt', 'secret-456', 'Bia');
    $this->post('/login', ['email' => 'bia@x.pt', 'password' => 'secret-456'])->assertRedirect('http://localhost/admin');
});

it('um login bem sucedido limpa o contador de falhas', function (): void {
    $this->makeUser();

    foreach (range(1, 4) as $_) {
        $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'errada']);
    }

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123'])->assertRedirect('http://localhost/admin');
    $this->post('/logout')->assertRedirect();

    foreach (range(1, 4) as $_) {
        $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'errada'])->assertStatus(302);
    }
});

it('o middleware auth redirecciona visitantes para /login e guarda o URL pretendido', function (): void {
    $this->makeUser();

    $this->get('/admin')->assertRedirect('http://localhost/login');
    expect($this->session('url.intended'))->toBe('http://localhost/admin');

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123'])
        ->assertRedirect('http://localhost/admin');

    expect($this->session('url.intended'))->toBeNull();
});

it('o middleware auth responde 401 JSON a pedidos que esperam JSON', function (): void {
    $this->getJson('/admin')
        ->assertUnauthorized()
        ->assertJson(['message' => 'Unauthenticated.']);
});

it('o middleware guest redirecciona quem já está autenticado', function (): void {
    $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);

    $this->get('/login')->assertRedirect('http://localhost/admin');
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123'])->assertRedirect('http://localhost/admin');
});

it('faz logout: invalida a sessão e deixa de dar acesso', function (): void {
    $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $this->get('/admin')->assertOk();

    $this->post('/logout')->assertRedirect('http://localhost');

    $this->get('/admin')->assertRedirect('http://localhost/login');
});

it('o logout exige autenticação', function (): void {
    $this->post('/logout')->assertRedirect('http://localhost/login');
});

it('$request->user() devolve o utilizador do guard activo', function (): void {
    $user = $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);

    $this->get('/whoami')->assertJson(['id' => $user->id]);

    $this->flushSession();
    $this->get('/whoami')->assertJson(['id' => null]);
});

it('o login com a sessão pré-existente de outro utilizador substitui-o', function (): void {
    $this->makeUser();
    $this->makeUser('bia@x.pt', 'secret-456', 'Bia');

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $this->post('/logout');
    $this->post('/login', ['email' => 'bia@x.pt', 'password' => 'secret-456']);

    $this->get('/admin')->assertSee('admin: Bia');
});

it('faz rehash do password no login se o custo do bcrypt mudou', function (): void {
    $user = $this->makeUser();
    $weak = password_hash('secret-123', PASSWORD_BCRYPT, ['cost' => 4]);
    User::query()->getQuery()->where('id', $user->id)->update(['password' => $weak]);

    $this->app->make(Slendie\Support\Config::class)->set('auth.hashing.bcrypt.rounds', 5);
    $this->app->forgetInstance(Slendie\Auth\Hashing\Hasher::class);
    $this->app->forgetInstance(AuthManager::class);

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123'])->assertRedirect();

    $stored = User::query()->where('id', $user->id)->first()->password;

    expect($stored)->not->toBe($weak)
        ->and(password_get_info($stored)['options']['cost'])->toBe(5)
        ->and(password_verify('secret-123', $stored))->toBeTrue();
});
