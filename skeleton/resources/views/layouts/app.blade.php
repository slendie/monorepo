<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    @include('partials.head')
    @stack('head')
</head>
<body>
    @include('partials.nav')
    @include('partials.flash')

    <main id="conteudo">
        @yield('content')
    </main>

    @include('partials.footer')

    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script src="{{ asset('js/app.js') }}" defer></script>
    @endunless
    @stack('scripts')
</body>
</html>
