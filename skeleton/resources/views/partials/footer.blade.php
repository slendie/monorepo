<footer class="rodape">
    <div class="container rodape-conteudo">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}</p>
        <nav aria-label="Informação legal">
            <a href="{{ route('privacy') }}">Privacidade</a>
            <a href="{{ route('terms') }}">Termos</a>
        </nav>
    </div>
</footer>
