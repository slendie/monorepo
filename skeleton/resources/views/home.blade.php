@extends('layouts.app')

@section('content')
    <section class="hero">
        <div class="container">
            <h1>A tua landing page, <span class="destaque">no ar em minutos</span></h1>
            <p class="lead">Rápida, leve e sem Node. Edita esta página, publica a pasta <code>public/</code> e começa a recolher contactos.</p>
            <a class="botao" href="#contacto">Fala connosco</a>
        </div>
    </section>

    <section class="container beneficios" aria-label="Benefícios">
        <article>
            <h2>Rápida</h2>
            <p>Uma página, um ficheiro de CSS e um de JavaScript. Sem frameworks no browser, sem passos de build.</p>
        </article>
        <article>
            <h2>Segura</h2>
            <p>CSRF, validação no servidor, cabeçalhos de segurança e um honeypot que trava a maior parte do spam.</p>
        </article>
        <article>
            <h2>Tua</h2>
            <p>Os contactos ficam na tua base de dados. Sem serviços externos, sem subscrições.</p>
        </article>
    </section>

    <section class="container contacto" id="contacto">
        <h2>Pede mais informação</h2>

        <form method="post" action="{{ route('contact.store') }}" data-contacto novalidate>
            @csrf

            <div class="campo">
                <label for="name">Nome</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" autocomplete="name" required>
                @error('name') <p class="erro">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                @error('email') <p class="erro">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="phone">Telefone <small>(opcional)</small></label>
                <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" autocomplete="tel">
                @error('phone') <p class="erro">{{ $message }}</p> @enderror
            </div>

            <div class="campo">
                <label for="message">Mensagem <small>(opcional)</small></label>
                <textarea id="message" name="message" rows="4">{{ old('message') }}</textarea>
                @error('message') <p class="erro">{{ $message }}</p> @enderror
            </div>

            {{-- Honeypot: escondido por CSS; os humanos não o vêem, os robots preenchem-no. --}}
            <div class="armadilha" aria-hidden="true">
                <label for="website">Não preencher</label>
                <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
            </div>

            <div class="campo campo-check">
                <input id="consent" name="consent" type="checkbox" value="1" @checked(old('consent')) required>
                <label for="consent">Li e aceito a <a href="{{ route('privacy') }}">política de privacidade</a>.</label>
            </div>
            @error('consent') <p class="erro">{{ $message }}</p> @enderror

            <button class="botao" type="submit">Enviar</button>
            <p class="estado" data-estado role="status" aria-live="polite"></p>
        </form>
    </section>
@endsection
