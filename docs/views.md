# Views e Blade

As views ficam em `resources/views` com extensão `.blade.php` e são **compiladas para PHP** em
`storage/framework/views` (recompiladas quando o ficheiro muda). O Blade da Slendie é um subconjunto fiel do Blade
do Laravel: o que existe comporta-se igual; o que não existe dá erro de compilação claro em vez de falhar em silêncio.

## Renderizar

```php
return view('home', ['title' => 'Olá', 'leads' => $leads]);
return view('admin.leads.index');          // resources/views/admin/leads/index.blade.php
view()->exists('home');
view()->share('appName', config('app.name'));             // em todas as views
view()->composer('partials.nav', fn (View $v) => $v->with('menu', Menu::all()));
```

## Echo

```blade
{{ $title }}                  {{-- escapado (htmlspecialchars) --}}
{!! $html !!}                 {{-- cru: só para HTML de confiança --}}
{{ $user->name ?? 'Anónimo' }}
{{ count($items) }}           {{-- qualquer expressão PHP --}}
@{{ nao_processado }}         {{-- literal, para JS frameworks --}}
{{-- comentário: não vai para o HTML --}}
```

## Layouts

```blade
{{-- resources/views/layouts/app.blade.php --}}
<!DOCTYPE html>
<html lang="pt">
<head>
    <title>@yield('title', config('app.name'))</title>
    @stack('head')
</head>
<body>
    @include('partials.nav')
    @yield('content')
    @stack('scripts')
</body>
</html>
```

```blade
{{-- resources/views/home.blade.php --}}
@extends('layouts.app')

@section('title', 'Início')

@section('content')
    <h1>{{ $title }}</h1>
@endsection

@push('scripts')
    <script src="/js/home.js"></script>
@endpush
```

`@section ... @show` define e imprime; `@parent` dentro de uma secção injecta o conteúdo do layout.

## Includes

```blade
@include('partials.alert', ['type' => 'success'])
@includeIf('partials.maybe')
@includeWhen($user, 'partials.user', ['user' => $user])
@includeUnless($guest, 'partials.user')
@includeFirst(['custom.header', 'header'])
```

A view incluída recebe todas as variáveis da view pai mais as passadas.

## Condicionais

```blade
@if ($leads->isEmpty())
    …
@elseif ($leads->count() === 1)
    …
@else
    …
@endif

@unless ($ok) … @endunless
@isset($x) … @endisset
@empty($x) … @endempty

@switch($status)
    @case('new') Novo @break
    @case('won') Ganho @break
    @default Outro
@endswitch

@env('local') … @endenv
@production … @endproduction
```

## Ciclos

```blade
@foreach ($leads as $lead)
    {{ $loop->iteration }}. {{ $lead->name }}
    @if ($loop->first) … @endif
    @if ($loop->last) … @endif
    {{ $loop->index }} {{ $loop->remaining }} {{ $loop->count }} {{ $loop->even }} {{ $loop->odd }} {{ $loop->depth }} {{ $loop->parent?->iteration }}
@endforeach

@forelse ($leads as $lead)
    {{ $lead->name }}
@empty
    Sem leads.
@endforelse

@for ($i = 0; $i < 10; $i++) … @endfor
@while ($x) … @endwhile
@continue($cond)  @break($cond)
```

## Formulários

```blade
<form method="POST" action="{{ route('contact.store') }}">
    @csrf
    @method('PUT')

    <input name="email" value="{{ old('email') }}" @required(true)>
    @error('email')
        <span class="error">{{ $message }}</span>
    @enderror

    <input type="checkbox" name="consent" @checked(old('consent'))>
    <option value="a" @selected($v === 'a')>A</option>
    <button @disabled($sending)>Enviar</button>
</form>

@if ($errors->any())
    <ul>@foreach ($errors->all() as $e) <li>{{ $e }}</li> @endforeach</ul>
@endif
```

## PHP e outros

```blade
@php
    $total = $leads->sum('value');
@endphp
@php($x = 1)

@json($config)                          {{-- json_encode seguro para <script> --}}
@json($config, JSON_PRETTY_PRINT)
@class(['btn', 'btn-primary' => $primary, 'disabled' => ! $ok])

@verbatim
    {{ isto não é processado }}
@endverbatim

@once … @endonce                        {{-- renderiza uma vez por render (view de topo) --}}
@inject('metrics', 'App\Services\Metrics')
@dd($x)  @dump($x)

@vite(['resources/js/app.js'])          {{-- ver vite.md --}}
@viteReactRefresh                       {{-- só em desenvolvimento (npm run dev) --}}
```

CSS e JS dentro das views não são afectados: `@media`, `@import`, `@font-face`, `@keyframes`, `@supports`, `@charset`, `@page`,
`@container`, `@property`, `@layer`, `@tailwind`, `@apply`, `@theme`, `@utility`, `@source`, `@variant`, `@plugin`, `@config`
e `@reference` passam intactos. Para outro `@palavra` literal (por exemplo `@click` do Alpine/Vue) usa `@@click`.

Uma directiva só é reconhecida quando o `@` não está colado a uma letra ou número (`a@b.pt` e `logo@2x.png` ficam como estão);
por isso `texto@endif` não compila: separa as directivas do texto com um espaço, uma quebra de linha ou uma tag.

## Com `slendie/auth`

```blade
@auth … @endauth
@guest … @endguest
@auth('api') … @endauth
```

## Directivas personalizadas

Num Service Provider:

```php
use Slendie\Support\Facades\Blade;

Blade::directive('money', fn (string $expr) => "<?php echo number_format($expr, 2, ',', '.') . ' €'; ?>");
Blade::if('admin', fn () => auth()->user()?->is_admin);     // @admin … @elseadmin … @endadmin
```

## Namespaces (views de packages)

```blade
@include('auth::login')
```

O projecto pode sobrepor views de packages em `resources/views/vendor/{namespace}/`.

## O que **não** existe (e a alternativa)

| Laravel | Slendie |
|---|---|
| Components `<x-alert>` / `@component` | `@include('components.alert', [...])` |
| `@each` | `@foreach` + `@include` |
| `@lang` / `@choice` | `{{ __('key') }}` |
| `@can` / `@cannot` | `@if (auth()->user()?->can(...))` |
| `@livewire`, `@inertia` | — |
| Slots, attributes bag | — |

## Erros

- View inexistente → `ViewNotFoundException` com a lista de pastas procuradas.
- Directiva desconhecida → `CompileException` com ficheiro e linha.
- Excepção dentro de uma view → `ViewException` com o nome da view e a **linha original** do `.blade.php`.

## Cache

```bash
php slendie view:clear
```

Com `APP_DEBUG=true` a recompilação é automática quando o ficheiro muda.
