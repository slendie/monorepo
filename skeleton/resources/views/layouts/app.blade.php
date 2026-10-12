<!doctype html>
<html lang="{{ config('app.locale', 'pt') }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', config('app.name', 'Slendie'))</title>
    @stack('head')
</head>
<body>
    @yield('content')
    @stack('scripts')
</body>
</html>
