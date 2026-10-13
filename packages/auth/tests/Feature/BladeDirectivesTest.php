<?php

declare(strict_types=1);

use Slendie\Auth\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('@guest mostra o conteúdo a visitantes e esconde o de @auth', function (): void {
    $response = $this->get('/directives');

    $response->assertOk()->assertSee('OUT')->assertDontSee('IN')->assertDontSee('API');
});

it('@auth mostra o conteúdo a quem está autenticado e esconde o de @guest', function (): void {
    $this->makeUser();
    $this->post('/login', ['email' => 'ana@x.pt', 'password' => 'secret-123']);

    $response = $this->get('/directives');

    $response->assertOk()->assertSee('IN')->assertDontSee('OUT');
});

it('@auth("api") avalia o guard indicado', function (): void {
    $user = $this->makeUser();

    $this->actingAs($user, 'api')->get('/directives')->assertSee('API');
});

it('as directivas compilam para @if/@else/@endif com Blade::check', function (): void {
    $compiled = $this->app->make(Slendie\View\Compiler\BladeCompiler::class)->compileString("@auth A @elseauth('api') B @else C @endauth @guest('api') D @endguest");

    expect($compiled)->toContain("BladeCompiler::check('auth')")
        ->and($compiled)->toContain("elseif (\\Slendie\\View\\Compiler\\BladeCompiler::check('auth', 'api'))")
        ->and($compiled)->toContain("BladeCompiler::check('guest', 'api')")
        ->and($compiled)->toContain('<?php endif; ?>');
});
