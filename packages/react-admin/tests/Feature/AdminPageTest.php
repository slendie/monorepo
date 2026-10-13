<?php

declare(strict_types=1);

use Slendie\ReactAdmin\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

it('GET /admin sem sessão redirecciona para /login', function (): void {
    $this->get('/admin')->assertRedirect('/login');
    $this->get('/admin/leads/3')->assertRedirect('/login');
});

it('a API recusa visitantes com 401 em JSON', function (): void {
    foreach (['me', 'stats', 'leads', 'leads/1'] as $path) {
        $this->getJson("/api/admin/{$path}")->assertUnauthorized();
    }

    $this->patchJson('/api/admin/leads/1', ['status' => 'won'])->assertUnauthorized();
    $this->deleteJson('/api/admin/leads/1')->assertUnauthorized();
});

it('GET /admin autenticado serve a view com #app, meta CSRF e config JSON com o utilizador', function (): void {
    $user = $this->makeUser();

    $response = $this->actingAs($user)->get('/admin')->assertOk();
    $html = $response->getContent();

    expect($html)->toContain('id="app"')
        ->and($html)->toContain('<meta name="csrf-token" content="')
        ->and($html)->toContain('name="robots" content="noindex, nofollow"')
        // Produção: @vite lê o manifest.
        ->and($html)->toContain('assets/main-abc123.js')
        ->and($html)->toContain('assets/main-abc123.css');

    preg_match("/data-config='([^']+)'/", $html, $match);
    $config = json_decode(html_entity_decode($match[1] ?? ''), true);

    expect($config['user'])->toBe(['id' => $user->id, 'name' => 'Ana', 'email' => 'ana@x.pt'])
        ->and($config['statuses'])->toBe(['new', 'contacted', 'qualified', 'lost', 'won'])
        ->and($config['api'])->toBe('/api/admin');
});

it('qualquer sub-rota de /admin devolve a mesma view (a SPA trata do router)', function (): void {
    $this->actingAs($this->makeUser());

    $this->get('/admin/leads')->assertOk()->assertSee('id="app"', false);
    $this->get('/admin/leads/12')->assertOk()->assertSee('id="app"', false);
    $this->get('/admin/nao/existe')->assertOk()->assertSee('id="app"', false);
});
