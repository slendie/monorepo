@extends('layouts.app')

@section('title', 'Política de privacidade — '.config('app.name'))

@section('content')
    <article class="container pagina texto">
        <h1>Política de privacidade</h1>
        <p><em>Modelo de partida: adapta-o ao teu negócio e revê-o com um profissional antes de publicar.</em></p>

        <h2>Que dados recolhemos</h2>
        <p>Através do formulário de contacto recolhemos o nome, o email e, opcionalmente, o telefone e a mensagem. Guardamos ainda o endereço IP e o <em>user agent</em> do browser para prevenir abusos.</p>

        <h2>Para que os usamos</h2>
        <p>Apenas para responder ao teu pedido de contacto. Não vendemos nem partilhamos estes dados com terceiros.</p>

        <h2>Base legal e conservação</h2>
        <p>O tratamento assenta no teu consentimento, dado ao submeter o formulário. Conservamos os dados durante o tempo necessário para responder e para cumprir obrigações legais.</p>

        <h2>Os teus direitos</h2>
        <p>Podes pedir o acesso, a rectificação ou o apagamento dos teus dados a qualquer momento, contactando-nos através do formulário.</p>

        <h2>Cookies</h2>
        <p>Este site usa apenas um cookie de sessão, necessário ao funcionamento do formulário (protecção CSRF). Não usa cookies de publicidade nem de análise.</p>
    </article>
@endsection
