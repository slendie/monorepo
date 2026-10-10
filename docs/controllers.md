# Controllers

```bash
php slendie make:controller LeadController
php slendie make:controller Admin/LeadController --resource
php slendie make:controller Api/LeadController --api --model=Lead
php slendie make:controller ContactController --invokable
```

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use Slendie\Http\Request;
use Slendie\Http\ViewResponse;
use Slendie\Http\RedirectResponse;

final class LeadController extends Controller      // Slendie\Http\Controller é opcional
{
    public function __construct(private readonly LeadRepository $leads) {}   // DI pelo container

    public function index(Request $request): ViewResponse
    {
        $leads = Lead::search($request->query('q'))->latest()->paginate(20);

        return view('admin.leads.index', ['leads' => $leads]);
    }

    public function show(Lead $lead): ViewResponse                            // route-model binding
    {
        return view('admin.leads.show', compact('lead'));
    }

    public function update(Request $request, Lead $lead): RedirectResponse
    {
        $data = $request->validate(['status' => 'required|in:new,contacted,won,lost']);   // peça de validação
        $lead->update($data);

        return redirect()->route('admin.leads.show', $lead)->with('success', 'Actualizado.');
    }

    public function destroy(Lead $lead): RedirectResponse
    {
        $lead->delete();

        return back()->with('success', 'Removido.');
    }
}
```

## Injecção de dependências

O container resolve automaticamente:
- argumentos do **construtor** tipados com classes concretas ou interfaces com *binding*;
- argumentos do **método** da acção: `Request`, serviços, models (route-model binding via `UrlRoutable`), `FormRequest` (validação automática, peça 08) e parâmetros de rota pelo nome, convertidos para o tipo declarado (`int`, `float`, `bool`).

O controller é instanciado uma vez por pedido (o construtor corre antes do middleware da rota, para poder declarar middleware).

## Helpers disponíveis

| Helper | Devolve |
|---|---|
| `view('nome', $data)` (peça 03), `response()->view('nome', $data)` | `View` / `ViewResponse` |
| `redirect('/x')`, `redirect()->route('n')`, `back()` | `RedirectResponse` |
| `response('texto', 201)`, `response()->json($data)`, `response()->noContent()`, `response()->download($path)` | `Response` |
| `request()` / `request('campo')` | `Request` / valor do input |
| `route('nome', $params)`, `url('/x')`, `asset('css/app.css')` | URL absoluto |
| `abort(404)`, `abort_if($cond, 403)`, `abort_unless(...)` | lança `HttpException` |
| `session('chave')`, `session()->flash('ok', 'Guardado')` | sessão |
| `old('campo')`, `csrf_token()`, `csrf_field()`, `method_field('PUT')` | para formulários |
| `cookie('nome', 'valor', minutos)` | `Cookie` (para `$response->cookie($c)`) |

## Middleware por controller

```php
public function __construct()
{
    $this->middleware('auth');                  // alias, classe ou 'alias:param1,param2'
    $this->middleware(['auth', 'verified']);
}
```

O middleware declarado aplica-se a **todas** as acções do controller; para restringir a algumas, usa `Route::...->middleware()`
na rota ou um grupo. O `Controller` base também oferece os atalhos protegidos `view()`, `redirect()`, `json()` e `response()`.

## Controller invocável

```php
final class ContactController extends Controller
{
    public function __invoke(StoreLeadRequest $request): RedirectResponse { ... }
}
Route::post('/contacto', ContactController::class);
```

## API controllers

Devolve arrays, `JsonSerializable` ou `Arrayable` (e, com as peças 04/05, models, collections e paginators) e a Slendie converte em JSON. Para controlar status/headers:

```php
return response()->json(['id' => $lead->id], 201);
return $lead;                       // 200, toArray() com hidden aplicados
return Lead::paginate(20);          // {data: [...], total, per_page, current_page, last_page, from, to}
```
