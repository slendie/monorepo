<?php

declare(strict_types=1);

use Slendie\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('grava o lead e volta ao formulário com uma mensagem', function (): void {
    $this->from('/')
        ->post('/contact', ['name' => 'Ana Silva', 'email' => 'ana@exemplo.pt', 'message' => 'Olá!'])
        ->assertRedirect('/')
        ->assertSessionHas('status');

    $this->assertDatabaseHas('leads', ['email' => 'ana@exemplo.pt', 'source' => 'contact']);
    $this->assertDatabaseCount('leads', 1);
});

it('valida os campos obrigatórios', function (): void {
    $this->from('/')
        ->post('/contact', ['email' => 'não é um email'])
        ->assertRedirect('/')
        ->assertSessionHasErrors(['name', 'email']);

    $this->assertDatabaseEmpty('leads');
});

it('responde 422 em JSON', function (): void {
    $this->postJson('/contact', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);
});

it('rejeita o honeypot preenchido por robots', function (): void {
    $this->postJson('/contact', ['name' => 'Bot', 'email' => 'bot@x.pt', 'website' => 'http://spam'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('website');

    $this->assertDatabaseMissing('leads', ['email' => 'bot@x.pt']);
});

it('cada teste começa com a tabela vazia (RefreshDatabase isola os testes)', function (): void {
    $this->assertDatabaseEmpty('leads');
});
