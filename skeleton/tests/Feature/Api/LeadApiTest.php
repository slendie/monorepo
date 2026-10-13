<?php

declare(strict_types=1);

use App\Models\Lead;
use Slendie\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['app.api_token' => 'segredo-de-teste']);
});

function apiHeaders(string $token = 'segredo-de-teste'): array
{
    return ['Authorization' => 'Bearer '.$token];
}

function makeLead(array $overrides = []): Lead
{
    return Lead::create([
        'name' => 'Ana Silva',
        'email' => 'ana@exemplo.pt',
        'status' => 'new',
        ...$overrides,
    ]);
}

it('recusa pedidos sem token', function (): void {
    $this->getJson('/api/leads')->assertUnauthorized();
});

it('recusa um token errado', function (): void {
    $this->getJson('/api/leads', apiHeaders('errado'))->assertUnauthorized();
});

it('fica desactivada (503) quando API_TOKEN está vazio', function (): void {
    config(['app.api_token' => '']);

    $this->getJson('/api/leads', apiHeaders(''))->assertStatus(503);
});

it('lista os leads paginados', function (): void {
    foreach (range(1, 3) as $i) {
        makeLead(['email' => "lead{$i}@exemplo.pt"]);
    }

    $this->getJson('/api/leads?per_page=2', apiHeaders())
        ->assertOk()
        ->assertJsonPath('total', 3)
        ->assertJsonPath('per_page', 2)
        ->assertJsonCount(2, 'data');
});

it('filtra por estado e pesquisa', function (): void {
    makeLead(['name' => 'Rui Costa', 'email' => 'rui@exemplo.pt', 'status' => 'contacted']);
    makeLead();

    $this->getJson('/api/leads?status=contacted', apiHeaders())->assertJsonPath('total', 1);
    $this->getJson('/api/leads?search=ana', apiHeaders())->assertJsonPath('total', 1);
});

it('mostra um lead', function (): void {
    $lead = makeLead();

    $this->getJson('/api/leads/'.$lead->id, apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.email', 'ana@exemplo.pt');

    $this->getJson('/api/leads/999', apiHeaders())->assertNotFound();
});

it('actualiza o estado', function (): void {
    $lead = makeLead();

    $this->putJson('/api/leads/'.$lead->id, ['status' => 'contacted'], apiHeaders())
        ->assertOk()
        ->assertJsonPath('data.status', 'contacted');

    $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'contacted']);
});

it('rejeita um estado inválido', function (): void {
    $lead = makeLead();

    $this->putJson('/api/leads/'.$lead->id, ['status' => 'inventado'], apiHeaders())
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('apaga um lead', function (): void {
    $lead = makeLead();

    $this->deleteJson('/api/leads/'.$lead->id, [], apiHeaders())->assertNoContent();

    $this->assertDatabaseEmpty('leads');
});

it('calcula as iniciais e o scope new', function (): void {
    makeLead(['name' => 'ana maria silva']);
    makeLead(['email' => 'b@x.pt', 'status' => 'lost']);

    expect(Lead::query()->new()->count())->toBe(1)
        ->and(Lead::query()->first()->initials)->toBe('AM');
});
