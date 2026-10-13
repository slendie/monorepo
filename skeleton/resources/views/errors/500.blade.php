@extends('layouts.app')

@section('title', '500 — '.config('app.name'))

@section('content')
    <section class="container pagina centro">
        <h1>500</h1>
        <p class="lead">Ocorreu um erro. Tente novamente mais tarde.</p>
        <a class="botao" href="/">Voltar ao início</a>
    </section>
@endsection
