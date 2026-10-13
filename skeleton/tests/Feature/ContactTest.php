<?php

declare(strict_types=1);

use Slendie\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function validLead(array $overrides = []): array
{
    return [
        'name' => 'Ana Silva',
        'email' => 'ana@exemplo.pt',
        'message' => 'Olá!',
        'consent' => '1',
        ...$overrides,
    ];
}

it('grava o lead e redirecciona para o obrigado', function (): void {
    $this->from('/')
        ->post('/contact', validLead())
        ->assertRedirect(route('thanks'))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('leads', ['email' => 'ana@exemplo.pt', 'source' => 'landing', 'status' => 'new']);
    $this->assertDatabaseCount('leads', 1);
});

it('valida os campos obrigatórios e volta ao formulário', function (): void {
    $this->from('/')
        ->post('/contact', ['email' => 'não é um email'])
        ->assertRedirect('/')
        ->assertSessionHasErrors(['name', 'email', 'consent']);

    $this->assertDatabaseEmpty('leads');
});

it('exige o consentimento', function (): void {
    $this->from('/')
        ->post('/contact', validLead(['consent' => '']))
        ->assertSessionHasErrors('consent');

    $this->assertDatabaseEmpty('leads');
});

it('rejeita o honeypot preenchido por robots', function (): void {
    $this->from('/')
        ->post('/contact', validLead(['email' => 'bot@x.pt', 'website' => 'http://spam']))
        ->assertSessionHasErrors('website');

    $this->assertDatabaseMissing('leads', ['email' => 'bot@x.pt']);
});

it('responde 201 em JSON', function (): void {
    $this->postJson('/contact', validLead())
        ->assertCreated()
        ->assertJsonStructure(['message', 'id']);

    $this->assertDatabaseCount('leads', 1);
});

it('responde 422 em JSON quando os dados são inválidos', function (): void {
    $this->postJson('/contact', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'consent']);
});
