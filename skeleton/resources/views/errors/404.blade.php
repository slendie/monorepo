@extends('layouts.app')

@section('title', '404 — '.config('app.name'))

@section('content')
    <section class="container pagina centro">
        <h1>404</h1>
        <p class="lead">Página não encontrada.</p>
        <a class="botao" href="/">Voltar ao início</a>
    </section>
@endsection
