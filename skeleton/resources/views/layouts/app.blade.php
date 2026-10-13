<!doctype html>
<html lang="{{ config('app.locale', 'pt') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Slendie'))</title>
    {{-- Com Vite (npm run dev / build) usa @vite; sem Node cai no CSS/JS vanilla de public/. --}}
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    @stack('head')
</head>
<body>
    @yield('content')
    @unless (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        <script src="{{ asset('js/app.js') }}" defer></script>
    @endunless
    @stack('scripts')
</body>
</html>
