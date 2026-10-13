<?php

declare(strict_types=1);

use Slendie\Auth\AuthManager;
use Slendie\Auth\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('actingAs autentica no guard web sem passar pelo login', function (): void {
    $user = $this->makeUser();

    $this->actingAs($user)->get('/admin')->assertOk()->assertSee('admin: Ana');
    $this->assertAuthenticated()->assertAuthenticatedAs($user);
});

it('actingAs autentica no guard api sem token', function (): void {
    $user = $this->makeUser();

    $this->actingAs($user, 'api')->getJson('/api/ping')->assertOk()->assertJson(['id' => $user->id]);
    $this->assertAuthenticated('api')->assertAuthenticatedAs($user, 'api');
});

it('actingAs persiste entre pedidos do mesmo teste', function (): void {
    $user = $this->makeUser();
    $this->actingAs($user);

    $this->get('/admin')->assertOk();
    $this->get('/admin')->assertOk();
    $this->get('/whoami')->assertJson(['id' => $user->id]);
});

it('actingAs num guard não autentica o outro', function (): void {
    $user = $this->makeUser();
    $this->actingAs($user, 'api');

    $this->get('/admin')->assertRedirect('/login');
    $this->assertGuest('web');
});

it('assertGuest e assertAuthenticated reflectem o estado real', function (): void {
    $user = $this->makeUser();

    $this->assertGuest();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $this->get('/whoami');
    $this->assertAuthenticated()->assertAuthenticatedAs($user);

    $this->post('/logout');
    $this->get('/whoami');
    $this->assertGuest();
});

it('actingAs seguido de logout num pedido deixa o utilizador desautenticado', function (): void {
    $user = $this->makeUser();

    $this->actingAs($user)->post('/logout')->assertRedirect();
    $this->get('/admin')->assertRedirect('/login');
});

it('o estado do login não vaza entre pedidos: sessão apagada = visitante', function (): void {
    $this->makeUser();

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $this->get('/admin')->assertOk();

    $this->flushSession();
    $this->get('/admin')->assertRedirect('/login');
});

it('o serviço auth e o helper devolvem o mesmo AuthManager', function (): void {
    expect(app('auth'))->toBeInstanceOf(AuthManager::class)
        ->and(auth())->toBe(app('auth'))
        ->and(auth('web'))->toBe(app('auth')->guard('web'))
        ->and(auth('api'))->not->toBe(auth('web'));
});

it('um guard desconhecido lança uma excepção clara', function (): void {
    expect(fn () => auth('fantasma'))->toThrow(InvalidArgumentException::class, 'fantasma');
});

it('attempt/login/logout directos em auth() funcionam fora de um pedido', function (): void {
    $user = $this->makeUser();

    expect(auth()->attempt(['email' => 'ana@x.pt', 'password' => 'errada']))->toBeFalse()
        ->and(auth()->check())->toBeFalse()
        ->and(auth()->attempt(['email' => 'ana@x.pt', 'password' => 'secret-123']))->toBeTrue()
        ->and(auth()->id())->toBe($user->id)
        ->and(auth()->guest())->toBeFalse();

    auth()->logout();

    expect(auth()->check())->toBeFalse();

    auth()->login($user);
    expect(auth()->user()->email)->toBe('ana@x.pt');
});

it('once() autentica sem criar sessão e loginUsingId encontra o utilizador', function (): void {
    $user = $this->makeUser();

    expect(auth()->once(['email' => 'ana@x.pt', 'password' => 'secret-123']))->toBeTrue()
        ->and(auth()->id())->toBe($user->id);

    auth()->logout();

    expect(auth()->loginUsingId($user->id)?->email)->toBe('ana@x.pt')
        ->and(auth()->loginUsingId(999))->toBeNull();
});

it('auth() com guard sem estado recusa attempt()', function (): void {
    $this->app->make(AuthManager::class)->shouldUse('api');

    expect(fn () => auth()->attempt(['email' => 'a@x.pt', 'password' => 'x']))->toThrow(LogicException::class);
});

it('extend() regista um driver de guard próprio', function (): void {
    $user = $this->makeUser();
    $manager = $this->app->make(AuthManager::class);

    $manager->extend('fixed', fn ($app, $name, $config, $provider) => (new Slendie\Auth\Guards\TokenGuard($name, $provider, fn () => null))->setUser($user));
    $this->app->make(Slendie\Support\Config::class)->set('auth.guards.custom', ['driver' => 'fixed', 'provider' => 'users']);

    expect(auth('custom')->user()->email)->toBe('ana@x.pt');
});
