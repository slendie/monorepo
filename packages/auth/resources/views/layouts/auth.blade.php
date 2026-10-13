<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', config('app.name', 'Slendie'))</title>
    @if (file_exists(public_path('css/app.css')))
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @endif
    <style>
        .auth { min-height: 100vh; display: grid; place-items: center; padding: 1rem; font-family: system-ui, sans-serif; }
        .auth-card { width: min(100%, 24rem); }
        .auth-card h1 { font-size: 1.5rem; margin: 0 0 1rem; }
        .auth-card label { display: block; margin: 0 0 .25rem; font-weight: 600; }
        .auth-card input[type="email"], .auth-card input[type="password"] { width: 100%; padding: .6rem .75rem; margin: 0 0 1rem; font: inherit; border: 1px solid #94a3b8; border-radius: .4rem; }
        .auth-card .remember { display: flex; gap: .5rem; align-items: center; margin: 0 0 1rem; }
        .auth-card button { width: 100%; padding: .7rem; font: inherit; font-weight: 600; cursor: pointer; border: 0; border-radius: .4rem; background: #4338ca; color: #fff; }
        .auth-card .error { color: #b91c1c; margin: -.5rem 0 1rem; }
    </style>
    @stack('head')
</head>
<body>
    <main class="auth">
        <div class="auth-card">
            @yield('content')
        </div>
    </main>
</body>
</html>
