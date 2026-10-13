<?php

declare(strict_types=1);

use Slendie\Auth\Models\PersonalAccessToken;
use Slendie\Auth\Models\User;
use Slendie\Auth\NewAccessToken;
use Slendie\Auth\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('createToken devolve o texto em claro uma vez e guarda só o sha256', function (): void {
    $user = $this->makeUser();

    $token = $user->createToken('cli', ['leads.read']);

    expect($token)->toBeInstanceOf(NewAccessToken::class)
        ->and($token->plainTextToken)->toMatch('/^\d+\|[A-Za-z0-9]{40}$/')
        ->and((string) $token)->toBe($token->plainTextToken);

    $secret = explode('|', $token->plainTextToken)[1];
    $row = PersonalAccessToken::query()->first();

    expect($row->token)->toBe(hash('sha256', $secret))
        ->and($row->token)->not->toContain($secret)
        ->and($row->name)->toBe('cli')
        ->and($row->abilities)->toBe(['leads.read'])
        ->and($row->user_id)->toBe($user->id)
        ->and($row->expires_at)->toBeNull()
        ->and($row->toArray())->not->toHaveKey('token');
});

it('um token válido abre rotas auth:api e identifica o utilizador', function (): void {
    $user = $this->makeUser();
    $token = $user->createToken('cli')->plainTextToken;

    $this->withToken($token)->getJson('/api/ping')->assertOk()->assertJson(['id' => $user->id]);
});

it('aceita o segredo sem o prefixo do id', function (): void {
    $user = $this->makeUser();
    $secret = explode('|', $user->createToken('cli')->plainTextToken)[1];

    $this->withToken($secret)->getJson('/api/ping')->assertOk();
});

it('recusa pedidos sem token, com token desconhecido ou adulterado (401 JSON)', function (): void {
    $user = $this->makeUser();
    $token = $user->createToken('cli')->plainTextToken;

    $this->getJson('/api/ping')->assertUnauthorized()->assertJson(['message' => 'Unauthenticated.']);
    $this->withToken('1|'.str_repeat('a', 40))->getJson('/api/ping')->assertUnauthorized();
    $this->withToken($token.'x')->getJson('/api/ping')->assertUnauthorized();
    $this->withToken('')->getJson('/api/ping')->assertUnauthorized();
    $this->withToken('|')->getJson('/api/ping')->assertUnauthorized();
});

it('um pedido sem Accept: application/json na API também recebe 401 (não há página de login)', function (): void {
    $this->get('/api/ping')->assertUnauthorized();
});

it('recusa tokens expirados e aceita os que ainda não expiraram', function (): void {
    $user = $this->makeUser();

    $expired = $user->createToken('velho', ['*'], new DateTimeImmutable('-1 minute'))->plainTextToken;
    $valid = $user->createToken('novo', ['*'], new DateTimeImmutable('+1 hour'))->plainTextToken;

    $this->withToken($expired)->getJson('/api/ping')->assertUnauthorized();
    $this->withToken($valid)->getJson('/api/ping')->assertOk();
});

it('o middleware ability exige a ability no token', function (): void {
    $user = $this->makeUser();
    $read = $user->createToken('r', ['leads.read'])->plainTextToken;
    $write = $user->createToken('w', ['leads.write'])->plainTextToken;
    $all = $user->createToken('all')->plainTextToken;

    $this->withToken($read)->getJson('/api/write')->assertForbidden()->assertJson(['message' => 'Invalid ability provided.']);
    $this->withToken($write)->getJson('/api/write')->assertOk();
    $this->withToken($all)->getJson('/api/write')->assertOk();
});

it('tokenCan e currentAccessToken reflectem o token do pedido', function (): void {
    $user = $this->makeUser();
    $user->createToken('r', ['leads.read']);

    $token = $user->createToken('cli', ['leads.read', 'leads.write']);

    expect($user->tokenCan('leads.read'))->toBeFalse()
        ->and($user->currentAccessToken())->toBeNull();

    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertOk();

    $resolved = auth('api')->user();

    expect($resolved->currentAccessToken()->id)->toBe($token->accessToken->id)
        ->and($resolved->tokenCan('leads.read'))->toBeTrue()
        ->and($resolved->tokenCan('leads.write'))->toBeTrue()
        ->and($resolved->tokenCan('leads.delete'))->toBeFalse()
        ->and($user->tokens()->get())->toHaveCount(2);
});

it('actualiza last_used_at no máximo uma vez por minuto', function (): void {
    $user = $this->makeUser();
    $token = $user->createToken('cli');

    expect($token->accessToken->last_used_at)->toBeNull();

    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertOk();

    $first = PersonalAccessToken::query()->first();
    expect($first->last_used_at)->not->toBeNull();

    // Recua o registo 10 s: dentro da janela de 1 minuto, não volta a escrever.
    $recent = date('Y-m-d H:i:s', time() - 10);
    PersonalAccessToken::query()->getQuery()->where('id', $first->id)->update(['last_used_at' => $recent]);
    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertOk();
    expect(PersonalAccessToken::query()->first()->last_used_at->format('Y-m-d H:i:s'))->toBe($recent);

    // Fora da janela, actualiza.
    $old = date('Y-m-d H:i:s', time() - 120);
    PersonalAccessToken::query()->getQuery()->where('id', $first->id)->update(['last_used_at' => $old]);
    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertOk();
    expect(PersonalAccessToken::query()->first()->last_used_at->format('Y-m-d H:i:s'))->not->toBe($old);
});

it('o guard api não usa a sessão e o guard web não usa o token', function (): void {
    $user = $this->makeUser();
    $token = $user->createToken('cli')->plainTextToken;

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $this->getJson('/api/ping')->assertUnauthorized();
    $this->post('/logout');

    $this->withToken($token)->getJson('/admin')->assertUnauthorized();
});

it('um token apagado ou de um utilizador apagado deixa de servir', function (): void {
    $user = $this->makeUser();
    $token = $user->createToken('cli');

    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertOk();

    $token->accessToken->delete();
    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertUnauthorized();

    $other = $user->createToken('outro')->plainTextToken;
    User::query()->getQuery()->where('id', $user->id)->delete();
    $this->withToken($other)->getJson('/api/ping')->assertUnauthorized();
});

it('um pedido autenticado por sessão passa no middleware ability (acesso total do painel)', function (): void {
    $this->makeUser();
    $this->app->make('router')->get('/painel', fn () => 'ok')->middleware(['web', 'auth', 'ability:leads.write']);

    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);
    $this->get('/painel')->assertOk();
});

it('validate() do TokenGuard confirma tokens sem os registar como utilizador', function (): void {
    $user = $this->makeUser();
    $token = $user->createToken('cli')->plainTextToken;

    expect(auth('api')->validate(['token' => $token]))->toBeTrue()
        ->and(auth('api')->validate(['token' => 'x']))->toBeFalse()
        ->and(auth('api')->validate([]))->toBeFalse()
        ->and(auth('api')->check())->toBeFalse();
});
