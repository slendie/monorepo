<?php

declare(strict_types=1);

it('mostra a página inicial', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('home')
        ->assertViewHas('title', 'Slendie')
        ->assertSee('Slendie');
});

it('responde 404 a páginas que não existem', function (): void {
    $this->get('/nao-existe')->assertNotFound();
});

it('envia os cabeçalhos de segurança', function (): void {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
