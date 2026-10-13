@extends('layouts.app')

@section('title', 'Obrigado — '.config('app.name'))

@section('content')
    <section class="container pagina centro">
        <h1>Obrigado!</h1>
        <p class="lead">Recebemos o seu pedido e respondemos o mais depressa possível.</p>
        <a class="botao" href="{{ route('home') }}">Voltar ao início</a>
    </section>
@endsection
