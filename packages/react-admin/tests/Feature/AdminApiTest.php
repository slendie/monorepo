<?php

declare(strict_types=1);

use App\Models\Lead;
use Slendie\ReactAdmin\Tests\TestCase;
use Slendie\Testing\RefreshDatabase;

uses(TestCase::class, RefreshDatabase::class);

beforeEach(function (): void {
    $this->actingAs($this->makeUser());
});

it('GET me devolve o utilizador autenticado', function (): void {
    $this->getJson('/api/admin/me')->assertOk()->assertJson(['name' => 'Ana', 'email' => 'ana@x.pt']);
});

it('lista leads paginados, mais recentes primeiro', function (): void {
    foreach (range(1, 5) as $i) {
        $this->makeLead(['name' => "Lead {$i}", 'email' => "l{$i}@x.pt"]);
    }

    $this->getJson('/api/admin/leads?per_page=2&page=2')
        ->assertOk()
        ->assertJsonPath('total', 5)
        ->assertJsonPath('per_page', 2)
        ->assertJsonPath('current_page', 2)
        ->assertJsonPath('last_page', 3)
        ->assertJsonPath('data.0.name', 'Lead 3');
});

it('filtra por estado e pesquisa por nome ou email', function (): void {
    $this->makeLead(['name' => 'Ana Silva', 'email' => 'ana@a.pt', 'status' => 'new']);
    $this->makeLead(['name' => 'Rui Pinto', 'email' => 'rui@silva.pt', 'status' => 'won']);
    $this->makeLead(['name' => 'Eva Reis', 'email' => 'eva@e.pt', 'status' => 'won']);

    $this->getJson('/api/admin/leads?status=won')->assertJsonPath('total', 2);
    $this->getJson('/api/admin/leads?q=silva')->assertJsonPath('total', 2);
    $this->getJson('/api/admin/leads?q=silva&status=won')->assertJsonPath('total', 1)->assertJsonPath('data.0.name', 'Rui Pinto');
    $this->getJson('/api/admin/leads?q=zzz')->assertJsonPath('total', 0);
});

it('ordena por coluna permitida e ignora colunas desconhecidas', function (): void {
    $this->makeLead(['name' => 'Beto', 'email' => 'b@x.pt']);
    $this->makeLead(['name' => 'Ana', 'email' => 'a@x.pt']);

    $this->getJson('/api/admin/leads?sort=name&dir=asc')->assertJsonPath('data.0.name', 'Ana');
    $this->getJson('/api/admin/leads?sort=name&dir=desc')->assertJsonPath('data.0.name', 'Beto');
    $this->getJson('/api/admin/leads?sort=password;drop&dir=asc')->assertOk();
});

it('limita per_page a 100', function (): void {
    $this->getJson('/api/admin/leads?per_page=9999')->assertJsonPath('per_page', 100);
});

it('mostra um lead e devolve 404 se não existir', function (): void {
    $lead = $this->makeLead();

    $this->getJson("/api/admin/leads/{$lead->id}")->assertOk()->assertJsonPath('data.email', 'bruno@exemplo.pt');
    $this->getJson('/api/admin/leads/999')->assertNotFound();
});

it('PATCH muda o estado e as notas', function (): void {
    $lead = $this->makeLead();

    $this->patchJson("/api/admin/leads/{$lead->id}", ['status' => 'contacted', 'notes' => 'Ligar amanhã'])
        ->assertOk()
        ->assertJsonPath('data.status', 'contacted')
        ->assertJsonPath('data.notes', 'Ligar amanhã');

    $fresh = Lead::find($lead->id);
    expect($fresh->status)->toBe('contacted')->and($fresh->notes)->toBe('Ligar amanhã');
});

it('PATCH valida o estado e não deixa alterar outros campos', function (): void {
    $lead = $this->makeLead();

    $this->patchJson("/api/admin/leads/{$lead->id}", ['status' => 'arquivado'])->assertStatus(422);
    $this->patchJson("/api/admin/leads/{$lead->id}", ['status' => ''])->assertStatus(422);

    $this->patchJson("/api/admin/leads/{$lead->id}", ['notes' => 'só notas', 'email' => 'hack@x.pt'])->assertOk();
    expect(Lead::find($lead->id)->email)->toBe('bruno@exemplo.pt')->and(Lead::find($lead->id)->status)->toBe('new');
});

it('DELETE apaga o lead', function (): void {
    $lead = $this->makeLead();

    $this->deleteJson("/api/admin/leads/{$lead->id}")->assertNoContent();

    expect(Lead::find($lead->id))->toBeNull();
    $this->deleteJson("/api/admin/leads/{$lead->id}")->assertNotFound();
});

it('stats conta por estado e os últimos 7 dias', function (): void {
    $this->makeLead(['status' => 'new']);
    $this->makeLead(['status' => 'new', 'email' => 'b@x.pt']);
    $this->makeLead(['status' => 'won', 'email' => 'c@x.pt']);

    $response = $this->getJson('/api/admin/stats')->assertOk()->assertJsonPath('total', 3);
    $stats = $response->json();

    expect($stats['by_status'])->toBe(['new' => 2, 'contacted' => 0, 'qualified' => 0, 'lost' => 0, 'won' => 1])
        ->and($stats['last_7_days'])->toHaveCount(7)
        ->and($stats['last_7_days'][6]['date'])->toBe(date('Y-m-d'))
        ->and($stats['last_7_days'][6]['count'])->toBe(3)
        ->and($stats['last_7_days'][0]['count'])->toBe(0);
});

it('exige o token CSRF em PATCH e DELETE (cabeçalho X-CSRF-TOKEN)', function (): void {
    $lead = $this->makeLead();
    $this->withCsrf();
    // Um GET (isento) abre a sessão; o token só é estável depois de ela existir.
    $this->getJson('/api/admin/me')->assertOk();
    $headers = ['X-CSRF-TOKEN' => $this->csrfToken()];

    $this->patchJson("/api/admin/leads/{$lead->id}", ['status' => 'won'])->assertStatus(419);
    $this->deleteJson("/api/admin/leads/{$lead->id}")->assertStatus(419);
    $this->patchJson("/api/admin/leads/{$lead->id}", ['status' => 'won'], ['X-CSRF-TOKEN' => 'errado'])->assertStatus(419);

    $this->patchJson("/api/admin/leads/{$lead->id}", ['status' => 'won'], $headers)->assertOk();
    $this->deleteJson("/api/admin/leads/{$lead->id}", [], $headers)->assertNoContent();
});
