# core-04-03 — Views e Blade compilado

> Peça 03 · Depende de: 01, 02 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Views), análise crítica §3.3

## Objectivo
Substituir o Blade interpretado das versões anteriores por um **compilador**: `.blade.php` → PHP nativo em
`storage/framework/views/{sha1}.php`, recompilado quando o ficheiro fonte muda (mtime) ou quando `APP_DEBUG=true`
e o conteúdo muda. `view('home', [...])` devolve `View` (Renderable) que o Router transforma em `ViewResponse`.

## Ficheiros a criar (`framework/src/View/`)
- `Factory.php`
  - `__construct(Finder $finder, Engine $engine, array $shared = [])`
  - `make(string $view, array $data = []): View`, `render(string $view, array $data = []): string`, `exists(string $view): bool`
  - `share(string|array $key, mixed $value = null)`, `getShared()`
  - `composer(string|array $views, Closure|string $callback)` — callback recebe `View` antes de renderizar; suporta `*` wildcard
  - `addNamespace(string $namespace, string|array $paths)` → views `auth::login`; `addLocation(string $path)`; `prependNamespace`
  - `startSection/stopSection/yieldContent/appendSection/startPush/stopPush/yieldPushContent/hasSection/getSection` — stack de secções usado pelo código compilado
  - `incrementRender/decrementRender/flushStateIfDoneRendering`
  - `addLoop/popLoop/incrementLoopIndices/getLastLoop` — suporte a `$loop`
  - `renderOnce` (para `@once`)
- `Finder.php` — resolve `layouts.app` → `{path}/layouts/app.blade.php`; procura em `paths` (ordem) e em `hints[namespace]`; cache de resultados; `ViewNotFoundException` com lista de paths procurados
- `View.php implements Renderable, Arrayable` — `with(key|array, value)`, `getData()`, `name()`, `path()`, `render(): string` (chama composers, mescla shared, chama `Engine`), `__toString`
- `Engine.php` — `get(string $compiledPath, array $data): string` — `extract($data, EXTR_SKIP)`, `ob_start`, `include`, com `try/finally` que limpa buffers; passa `$__env` (Factory) para o código compilado; excepções dentro da view são re-lançadas como `ViewException` com nome da view e linha original (mapa de linhas via comentários `/**PATH:LINE**/` inseridos pelo compilador a cada directiva)
- `Compiler/BladeCompiler.php`
  - `compile(string $path): void` escreve ficheiro compilado; `getCompiledPath(path)`, `isExpired(path)`
  - `compileString(string $value): string` — pipeline: extrair `@verbatim`, extrair `@php ... @endphp`, compilar comentários `{{-- --}}`, compilar echoes (`{!! !!}` depois `{{ }}` com `e()`; respeitar `@{{ }}` escape), compilar directivas (`@nome(expr)` com parênteses balanceados ou `@nome` sem args), restaurar raw blocks
  - Directivas via métodos `compileIf($expr)` etc. e `directive(string $name, callable $handler)` para customizadas (handler recebe a expressão como string e devolve PHP); `getCustomDirectives()`
  - `@extends` é extraído e emitido no **fim** do ficheiro compilado (`<?php echo $__env->make('layout', get_defined_vars())->render(); ?>`), como no Laravel; `@section/@endsection/@show/@yield/@parent/@stop/@overwrite`
  - Directiva desconhecida (`@foo` não registada, não precedida de `@@`, e que corresponda a `@[a-zA-Z_]\w*`) → `CompileException("Directiva @foo desconhecida em {view}:{linha}")`. Excepção: identificadores que fazem parte de CSS/JS (`@media`, `@import`, `@font-face`, `@keyframes`, `@supports`, `@charset`, `@page`, `@layer`, `@tailwind`, `@apply`) são deixados intactos. Para outros casos usa-se `@@`
- `Compiler/Directives/` (traits ou classes misturadas no compiler; uma por família):
  - `Layouts`: extends, section, endsection, stop, show, overwrite, yield, parent, include, includeIf, includeWhen, includeUnless, includeFirst, each (não), stack, push, endpush, prepend, endprepend, once, endonce
  - `Conditionals`: if, elseif, else, endif, unless, endunless, isset, endisset, empty, endempty, switch, case, break, default, endswitch, env, endenv, production, endproduction
  - `Loops`: foreach, endforeach, forelse, empty (contextual), endforelse, for, endfor, while, endwhile, break(cond), continue(cond); gera `$loop` (index, iteration, remaining, count, first, last, even, odd, depth, parent)
  - `Echos`: `{{ }}`, `{!! !!}`, `{{{ }}}` (não), `@json($expr, $flags?)`
  - `Forms`: `@csrf`, `@method('PUT')`, `@error('field') ... @enderror` (expõe `$message`; usa `$errors` MessageBag — nesta peça `$errors` é um objecto simples com `has()/first()`; peça 08 substitui por `MessageBag` real), `@old`? não (usar `old()`)
  - `Assets`: `@vite(['resources/css/app.css', 'resources/js/app.js'])` → chama `Vite::tags()` (classe real na peça 09; aqui a directiva compila para `<?php echo vite(...); ?>` e o helper existe como stub que devolve string vazia + TODO)
  - `Misc`: `@php/@endphp`, `@php($expr)` inline, `@verbatim/@endverbatim`, `@dd($x)`, `@dump($x)`, `@inject('var', 'Class')`, `@class([...])` (array → string de classes condicionais), `@checked/@selected/@disabled/@readonly/@required(cond)`, `@auth/@guest/@endauth/@endguest` **não** aqui: são registadas pelo package auth via `directive()`
- `ViewServiceProvider.php` — regista `Factory`, `Finder` (paths: `resource_path('views')`), `BladeCompiler` (cache: `storage_path('framework/views')`), `Engine`; alias `'view'`, `'blade.compiler'`; facade `Support\Facades\{View, Blade}`
- `ViewNotFoundException`, `ViewException`, `CompileException`
- `Support/helpers.php` — `view(?string $view = null, array $data = []): View|Factory`
- Converter as páginas de erro de `framework/resources/views/errors/*.php` para `.blade.php` com um layout `errors/layout.blade.php`; `Handler` passa a usar `view('errors::404')` (namespace `errors` registado pelo provider apontando para a pasta da framework; o projecto pode sobrepor criando `resources/views/errors/404.blade.php`, que o Finder procura primeiro)
- `Console` fica para a peça 07, mas deixar método `BladeCompiler::clearCache()` pronto para `view:clear`

## Regras de compilação (precisão)
- Echo escapado: `{{ $x }}` → `<?php echo e($x); ?>`; suporta `{{ $x ?? 'default' }}` e qualquer expressão PHP (não se avalia nada em PHP-land; é código).
- Espaços à volta de echoes preservados; `@{{ }}` → literal `{{ }}` (para JS frameworks).
- `@foreach($items as $item)` → `<?php $__env->addLoop($items); foreach($items as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>` … `@endforeach` → `<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>`
- `@forelse` com `@empty` contextual: o compilador precisa de saber se `@empty` fecha um `@forelse` ou abre um `@empty($x)`; distinguir por presença de parênteses imediatamente após.
- `@include('partials.head', ['a' => 1])` → `<?php echo $__env->make('partials.head', array_merge(get_defined_vars(), ['a' => 1]))->render(); ?>` com remoção de `__env`, `__data`, `__path` do escopo.
- `@section('title', 'Texto')` inline → define e fecha.
- Mapa de linhas: antes de cada bloco PHP gerado, inserir `/**#L{n}**/`; `Engine` usa isso para traduzir a linha da excepção.

## Testes (Pest, snapshot-style: fonte Blade + dados → HTML esperado)
Cobrir todas as directivas listadas; herança com `@parent`; includes aninhados com dados; `$loop` em loops aninhados (`$loop->parent`); `@forelse` vazio; `@error` com e sem erro; `@json`; `@class`; `@verbatim`; `@@escape`; CSS `@media` intacto; directiva desconhecida lança `CompileException` com linha; directiva customizada; namespaces `pkg::view`; composers; `share`; cache invalidada por mtime; excepção dentro de view reporta nome e linha original; `ViewNotFoundException` lista paths.

## Critérios de aceitação
- Skeleton: `resources/views/layouts/app.blade.php` + `home.blade.php` renderizados em `/` via `Route::view('/', 'home', ['title' => 'Slendie'])`.
- Compilação de um template de 200 linhas < 5 ms; render cacheado < 0,5 ms (medição informativa, não teste).
- `composer test/analyse/lint` verdes.
- Documentação `docs/views.md` com a lista completa de directivas suportadas e das **não** suportadas (components `<x-…>`, `@each`, `@lang`, `@can`) com alternativa.

## Desvios
Implementado em 2026-10-11 (297 testes no total, PHPStan nível 8 e Pint verdes). Divergências face ao texto acima:

1. **O PHP compilado tem exactamente as mesmas linhas que o template; não há marcadores `/**#L{n}**/`.** Todas as transformações
   (echos, comentários, `@verbatim`, `@php`, directivas com argumentos em várias linhas) preservam o número de quebras de linha
   — o compilador guarda os troços já finais em marcadores com as mesmas linhas e o `?>` que engole uma quebra de linha é
   compensado com `echo "\n"` dentro do PHP. Por isso a linha de uma excepção no ficheiro compilado *é* a linha do `.blade.php`
   (o `Engine` usa-a directamente) e a `CompileException` conta as linhas na própria fonte. No fim do ficheiro compilado fica só
   `<?php /**PATH:{fonte}|HASH:{sha1 do conteúdo}**/ ?>` (usado pelo modo debug para detectar alterações).
2. **`Engine::get(string $path, array $data, Factory $env)` recebe o ficheiro *fonte*** (não o compilado) e compila-o se a cache
   estiver desactualizada: a `Factory` do spec só tem `Finder` e `Engine`, logo o `Engine` é quem conhece o `BladeCompiler`.
3. **`@include`/`@extends` chamam `$__env->renderInclude(get_defined_vars(), 'view', [dados])`** (e `renderIncludeIf/When/Unless/First`),
   em vez de `make(..., array_merge(get_defined_vars(), ...))->render()`: a remoção de `__env`, `__data` e `__path` do escopo
   fica num só sítio e não é preciso partir os argumentos em tempo de compilação.
4. **`@foreach`/`@forelse` guardam a colecção em `$__currentLoopData`** antes de `addLoop()`/`foreach` (a expressão só é avaliada uma
   vez), como no Laravel. `$loop` é um objecto `View\Loop` (propriedades tipadas) e `$loop->parent` é o `Loop` do ciclo exterior.
5. **`Compiler/Directives/` são traits** (`Layouts`, `Conditionals`, `Loops`, `Echos`, `Forms`, `Assets`, `Misc`) misturadas no
   `BladeCompiler`. `{{ }}`, `{!! !!}`, comentários, `@verbatim` e `@php … @endphp` são tratados no pipeline do compilador (precisam
   de preservar linhas); `@json` fica em `Echos`. Os handlers de directivas personalizadas recebem a expressão **sem** os
   parênteses exteriores (`''` se não houver), como no Laravel.
6. **Regra do `@`:** uma directiva só é reconhecida se o `@` não estiver colado a uma letra/número (o `\B@` do Laravel), o que
   também protege emails e `logo@2x.png`. Efeito: `texto@endif` ou `@endsection@section` (directivas coladas, sem parênteses a
   separar) não compilam; é preciso um espaço, quebra de linha ou tag. At-rules de CSS/JS passam intactas (lista alargada ao Tailwind
   v4: `@theme`, `@utility`, `@source`, `@variant`, `@plugin`, `@config`, `@reference`, `@container`, `@property`, `@scope`…), e
   nomes seguidos de hífen (`@font-face`, `@custom-variant`) nunca são directivas.
7. **Directivas acrescentadas** além da lista: `@append`, `@hasSection` e `@sectionMissing`. `@env` aceita vários nomes ou um array. `@class` produz `class="…"` (como o Laravel) usando
   `Arr::toCssClasses()` (novo). `@json` aplica sempre `JSON_HEX_TAG|APOS|AMP|QUOT` (seguro em `<script>` e atributos), mesmo
   quando se passam flags. `@error('campo')` usa `$errors->has()/first()` e restaura o `$message` anterior no `@enderror`.
8. **`$errors` é sempre um `View\ViewErrorBag`** (`has/first/get/all/any/count/getBag`): a `Factory` partilha um vazio por omissão e o
   `ShareErrorsFromSession` substitui-o por `ViewErrorBag::fromSession()` (bag `default` do flash `errors` que o `withErrors()` grava).
   A peça 08 troca-o pelo `MessageBag`.
9. **`Router::toResponse()` renderiza as views dentro do pipeline** (altera o comportamento "lazy" da peça 02). Com render lazy, uma
   excepção na view ou um `abort()` dentro dela escapava ao `Kernel`/`Handler` (acontecia em `$response->send()`), e `old()`/flash já
   tinham sido envelhecidos pelo `StartSession`. O `ViewResponse` continua a poder ser construído lazy fora do Router.
10. **Contrato `Contracts\ViewFactory` ganhou `exists()`** (usado pelo `Handler`). `Handler` deixa de ter `findErrorTemplate()`/
    `renderTemplate()`: usa `renderErrorView()` com `errors::{status}` → `errors::generic` → HTML mínimo, e engole qualquer erro
    ao renderizar a própria página de erro.
11. **Páginas de erro** em `framework/resources/views/errors/*.blade.php` (+ `layout.blade.php`); as `.php` foram apagadas. O
    `Finder` regista o namespace `errors` com [`resources/views/errors` do projecto, pasta da framework] — o projecto vence.
12. **`ViewServiceProvider`** lê `view.paths` e `view.compiled` de `config/view.php` *se existir* (por omissão `resources/views` e
    `storage/framework/views`); a `Factory` recebe o container para composers em classe (`Classe` ou `Classe@metodo`).
    `ServiceProvider::loadViewsFrom()` continua para a peça 09. `@vite` compila para o helper `vite()` (stub que devolve `''`).
13. **Nomes de view são validados** (`[A-Za-z0-9_-]` separados por `.`, com `ns::` opcional); nomes como `../x` ou `a/b` lançam
    `ViewNotFoundException`. Excepções `HttpException` dentro de views passam sem serem embrulhadas em `ViewException`; um
    buffer por fechar (`@section` sem `@endsection`) é reportado como `ViewException`.
14. **Medições (informativas)**, Windows, PHP 8.5 sem Xdebug: compilar um template de 183 linhas ≈ 0,9 ms; render em cache de uma
    view mínima ≈ 0,4–0,7 ms (dominado pelos 3 `stat` de `isExpired()` no NTFS) e ≈ 3,7 ms para o template de 183 linhas com 60
    ciclos (≈ 180 iterações). O alvo de 0,5 ms só se cumpre em views pequenas neste ambiente.
15. **Verificado** com a `Application` do skeleton em processo (`GET /` → 200 com `layouts/app` + `home`, `GET /x` → 404, `POST /` →
    405). Cobertura de código não medida (sem driver Xdebug/PCOV neste ambiente).
