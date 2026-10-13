<?php

declare(strict_types=1);

it('mostra a página inicial', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('home')
        ->assertSee('no ar em minutos')
        ->assertSee('name="website"', false)
        ->assertSee('<meta name="description"', false);
});

it('mostra a página de obrigado e as páginas legais', function (): void {
    $this->get('/obrigado')->assertOk()->assertSee('Obrigado!');
    $this->get('/privacidade')->assertOk()->assertSee('Política de privacidade');
    $this->get('/termos')->assertOk()->assertSee('Termos de utilização');
});

it('responde 404 com a página do site', function (): void {
    $this->get('/nao-existe')
        ->assertNotFound()
        ->assertSee('Página não encontrada.');
});

it('envia os cabeçalhos de segurança', function (): void {
    $this->get('/')
        ->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'SAMEORIGIN');
});
