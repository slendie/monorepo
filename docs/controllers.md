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

final class LeadController extends Controller
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
        $data = $request->validate(['status' => 'required|in:new,contacted,won,lost']);
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
- argumentos do **método** da acção: `Request`, serviços, models (route-model binding), `FormRequest` (validação automática) e parâmetros de rota pelo nome.

## Helpers disponíveis

| Helper | Devolve |
|---|---|
| `view('nome', $data)` | `View` |
| `redirect('/x')`, `redirect()->route('n')`, `back()` | `RedirectResponse` |
| `response('texto', 201)`, `response()->json($data)`, `response()->noContent()` | `Response` |
| `request()` / `request('campo')` | `Request` / valor |
| `abort(404)`, `abort_if($cond, 403)`, `abort_unless(...)` | lança `HttpException` |
| `session('chave')`, `session()->flash('ok', 'Guardado')` | sessão |

## Middleware por controller

```php
public function __construct()
{
    $this->middleware('auth');
    $this->middleware('admin')->only(['destroy']);
}
```

## Controller invocável

```php
final class ContactController extends Controller
{
    public function __invoke(StoreLeadRequest $request): RedirectResponse { ... }
}
Route::post('/contacto', ContactController::class);
```

## API controllers

Devolve arrays, models, collections ou paginators e a Slendie converte em JSON. Para controlar status/headers:

```php
return response()->json(['id' => $lead->id], 201);
return $lead;                       // 200, toArray() com hidden aplicados
return Lead::paginate(20);          // {data: [...], total, per_page, current_page, last_page, from, to}
```
