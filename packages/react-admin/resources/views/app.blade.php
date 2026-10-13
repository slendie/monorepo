<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Slendie') }} · Admin</title>
    @viteReactRefresh
    @vite(['resources/admin/main.tsx'])
</head>
<body>
    <div id="app" data-config='@json($config)'></div>
</body>
</html>
