<?php

declare(strict_types=1);

use Slendie\Auth\Models\PersonalAccessToken;
use Slendie\Auth\Models\User;
use Slendie\Auth\Providers\ModelUserProvider;
use Slendie\Auth\Tests\Fixtures\Models\Member;
use Slendie\Auth\Tests\TestCase;
use Slendie\Database\Schema\Blueprint;
use Slendie\Support\Config;
use Slendie\Support\Facades\Schema;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    Schema::create('members', function (Blueprint $table): void {
        $table->id('member_id');
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->string('remember_token', 100)->nullable();
        $table->timestamps();
    });

    $this->app->make(Config::class)->set('auth.providers.users.model', Member::class);
});

afterEach(function (): void {
    Schema::dropIfExists('members');
});

it('o ModelUserProvider usa o model de config(auth.providers.users.model)', function (): void {
    $provider = auth()->provider();

    expect($provider)->toBeInstanceOf(ModelUserProvider::class)
        ->and($provider->model())->toBe(Member::class);
});

it('autentica um model customizado (outra tabela e chave primária) por sessão', function (): void {
    $member = Member::create(['name' => 'Rui', 'email' => 'rui@x.pt', 'password' => 'secret-123']);

    $this->post('/login', ['email' => 'rui@x.pt', 'password' => 'secret-123'])->assertRedirect('/admin');
    $this->get('/admin')->assertOk()->assertSee('admin: Rui');

    expect(auth()->user())->toBeInstanceOf(Member::class)
        ->and(auth()->id())->toBe($member->member_id);
});

it('o remember-me funciona com um model customizado', function (): void {
    $member = Member::create(['name' => 'Rui', 'email' => 'rui@x.pt', 'password' => 'secret-123']);

    $this->post('/login', ['email' => 'rui@x.pt', 'password' => 'secret-123', 'remember' => '1']);

    (function (): void {
        unset($this->cookies['slendie_session']);
        $this->sessionId = null;
    })->call($this);

    $this->get('/admin')->assertOk();
    expect(Member::query()->where('member_id', $member->member_id)->first()->remember_token)->toHaveLength(64);
});

it('os tokens de API pertencem ao model customizado', function (): void {
    $member = Member::create(['name' => 'Rui', 'email' => 'rui@x.pt', 'password' => 'secret-123']);
    $token = $member->createToken('cli');

    expect(PersonalAccessToken::query()->first()->user_id)->toBe($member->member_id);

    $this->withToken($token->plainTextToken)->getJson('/api/ping')->assertOk()->assertJson(['id' => $member->member_id]);
});

it('sem o model configurado existir, usa o User do package', function (): void {
    $this->app->make(Config::class)->set('auth.providers.users.model', 'App\\Models\\NaoExiste');

    expect(auth()->provider()->model())->toBe(User::class);
});

it('recusa um model que não implementa Authenticatable', function (): void {
    $this->app->make(Config::class)->set('auth.providers.users.model', stdClass::class);

    expect(fn () => auth()->provider())->toThrow(InvalidArgumentException::class, 'Authenticatable');
});

it('o provider procura por credenciais arbitrárias, ignora a password e aceita listas', function (): void {
    Member::create(['name' => 'Rui', 'email' => 'rui@x.pt', 'password' => 'secret-123']);
    $provider = auth()->provider();

    expect($provider->retrieveByCredentials(['email' => 'rui@x.pt', 'password' => 'qualquer'])?->email)->toBe('rui@x.pt')
        ->and($provider->retrieveByCredentials(['name' => 'Rui'])?->email)->toBe('rui@x.pt')
        ->and($provider->retrieveByCredentials(['email' => ['x@x.pt', 'rui@x.pt']])?->email)->toBe('rui@x.pt')
        ->and($provider->retrieveByCredentials(['email' => 'outro@x.pt']))->toBeNull()
        ->and($provider->retrieveByCredentials(['password' => 'só-password']))->toBeNull()
        ->and($provider->retrieveByCredentials([]))->toBeNull();
});

it('o provider só aceita ids escalares e nunca devolve utilizadores inexistentes', function (): void {
    $provider = auth()->provider();

    expect($provider->retrieveById(['x']))->toBeNull()
        ->and($provider->retrieveById(null))->toBeNull()
        ->and($provider->retrieveById(999))->toBeNull()
        ->and($provider->retrieveByToken(999, 'abc'))->toBeNull();
});

it('um driver de provider não suportado lança excepção', function (): void {
    $this->app->make(Config::class)->set('auth.providers.users.driver', 'database');

    expect(fn () => auth()->provider())->toThrow(InvalidArgumentException::class, 'database');
});
