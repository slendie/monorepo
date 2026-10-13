@php
    $seoTitle = trim($__env->yieldContent('title')) ?: config('app.seo.title');
    $seoDescription = trim($__env->yieldContent('description')) ?: config('app.seo.description');
    $seoImage = config('app.seo.image');
@endphp
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ $seoDescription }}">
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ $seoDescription }}">
@if ($seoImage)
    <meta property="og:image" content="{{ $seoImage }}">
@endif
<meta name="twitter:card" content="summary">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
{{-- Com Vite (npm run dev / build) usa @vite; sem Node cai no CSS/JS vanilla de public/. --}}
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
@else
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
@endif
