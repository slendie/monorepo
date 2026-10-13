<?php

declare(strict_types=1);

use Slendie\Auth\Models\User;
use Slendie\Auth\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

/** Um "browser novo": mantém a cookie remember-me mas perde a da sessão. */
function dropSessionCookie(TestCase $test): void
{
    (function (): void {
        unset($this->cookies['slendie_session']);
        $this->sessionId = null;
    })->call($test);
}

function cookieNamed(Slendie\Testing\TestResponse $response, string $name): ?Slendie\Http\Cookie
{
    foreach ($response->baseResponse->cookies() as $cookie) {
        if ($cookie->name === $name) {
            return $cookie;
        }
    }

    return null;
}

function rememberCookie(TestCase $test): ?string
{
    return (fn () => $this->cookies['slendie_remember'] ?? null)->call($test);
}

it('envia a cookie remember-me (HttpOnly, SameSite=Lax, 400 dias) só quando pedido', function (): void {
    $this->makeUser();

    $response = $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $response->assertCookieMissing('slendie_remember');

    $this->post('/logout');

    $response = $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);
    $cookie = cookieNamed($response, 'slendie_remember');

    expect($cookie)->not->toBeNull()
        ->and($cookie->httpOnly)->toBeTrue()
        ->and($cookie->sameSite)->toBe('lax')
        ->and($cookie->expires)->toBeGreaterThan(time() + 399 * 86400)
        ->and($cookie->expires)->toBeLessThan(time() + 401 * 86400)
        ->and(substr_count($cookie->value, '|'))->toBe(2);
});

it('guarda na base de dados só o hash do token remember-me, nunca o valor da cookie', function (): void {
    $user = $this->makeUser();

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);

    [$id, $token] = explode('|', (string) rememberCookie($this));
    $stored = User::query()->where('id', $user->id)->first()->remember_token;

    expect((int) $id)->toBe($user->id)
        ->and($stored)->toBe(hash('sha256', $token))
        ->and($stored)->not->toContain($token);
});

it('recria a sessão a partir da cookie remember-me', function (): void {
    $user = $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);

    dropSessionCookie($this);

    $this->get('/admin')->assertOk()->assertSee('admin: Ana');
    expect(auth()->viaRemember())->toBeTrue();

    // A sessão nova já tem o utilizador: o pedido seguinte não precisa da cookie.
    $this->get('/whoami')->assertJson(['id' => $user->id]);
});

it('sem remember-me, perder a sessão obriga a novo login', function (): void {
    $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);

    dropSessionCookie($this);

    $this->get('/admin')->assertRedirect('/login');
});

it('rejeita uma cookie remember-me adulterada ou assinada com outra chave', function (): void {
    $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);
    [$id, $token, $hmac] = explode('|', (string) rememberCookie($this));

    foreach ([
        "{$id}|{$token}|".strrev($hmac),
        "{$id}|{$token}|",
        "{$id}|".strrev($token)."|{$hmac}",
        "999|{$token}|{$hmac}",
        "{$id}|{$token}",
        'lixo',
    ] as $forged) {
        dropSessionCookie($this);
        $this->withCookie('slendie_remember', $forged)->get('/admin')->assertRedirect('/login');
    }

    // Uma cookie bem assinada (HMAC com outra chave) para o token certo também falha.
    $wrong = hash_hmac('sha256', "{$id}|{$token}", 'outra-chave');
    dropSessionCookie($this);
    $this->withCookie('slendie_remember', "{$id}|{$token}|{$wrong}")->get('/admin')->assertRedirect('/login');
});

it('o logout apaga a cookie e invalida o token remember-me (também numa cópia antiga)', function (): void {
    $user = $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);
    $stolen = (string) rememberCookie($this);

    $response = $this->post('/logout');
    $response->assertRedirect();

    $forgotten = cookieNamed($response, 'slendie_remember');
    expect($forgotten)->not->toBeNull()
        ->and($forgotten->isExpired())->toBeTrue()
        ->and(rememberCookie($this))->toBeNull();

    // O token da base de dados foi rodado: a cookie copiada deixa de servir.
    dropSessionCookie($this);
    $this->withCookie('slendie_remember', $stolen)->get('/admin')->assertRedirect('/login');

    expect(User::query()->where('id', $user->id)->first()->remember_token)->not->toBe(hash('sha256', explode('|', $stolen)[1]));
});

it('um novo login com remember-me invalida a cookie anterior (um dispositivo de cada vez)', function (): void {
    $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);
    $first = (string) rememberCookie($this);
    $this->post('/logout');

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);
    $second = (string) rememberCookie($this);

    expect($second)->not->toBe($first);

    dropSessionCookie($this);
    $this->withCookie('slendie_remember', $first)->get('/admin')->assertRedirect('/login');

    dropSessionCookie($this);
    $this->withCookie('slendie_remember', $second)->get('/admin')->assertOk();
});

it('apagar o utilizador torna a cookie remember-me inútil', function (): void {
    $user = $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123', 'remember' => '1']);
    $cookie = (string) rememberCookie($this);

    User::query()->getQuery()->where('id', $user->id)->delete();

    dropSessionCookie($this);
    $this->withCookie('slendie_remember', $cookie)->get('/admin')->assertRedirect('/login');
});
