# Tutorial: landing page com leads em 15 minutos

O caso para o qual a Slendie foi feita. O skeleton já traz isto a funcionar; o tutorial mostra como foi construído
para o adaptares.

## 1. Projecto

```bash
composer create-project slendie/slendie promo
cd promo && php slendie serve
```

## 2. Model e migração

```bash
php slendie make:model Lead -m
```

```php
// database/migrations/…_create_leads_table.php
Schema::create('leads', function (Blueprint $t) {
    $t->id();
    $t->string('name', 120);
    $t->string('email')->index();
    $t->string('phone', 30)->nullable();
    $t->text('message')->nullable();
    $t->string('status', 20)->default('new')->index();
    $t->string('source', 50)->nullable();
    $t->string('ip', 45)->nullable();
    $t->timestamps();
});
```

```php
// app/Models/Lead.php
final class Lead extends Model
{
    protected array $fillable = ['name', 'email', 'phone', 'message', 'status', 'source', 'ip'];
}
```

```bash
php slendie migrate
```

## 3. Rotas

```php
// routes/web.php
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/obrigado', [HomeController::class, 'thanks'])->name('thanks');
Route::view('/privacidade', 'privacy')->name('privacy');
```

## 4. Validação e controller

```bash
php slendie make:request StoreLeadRequest
php slendie make:controller ContactController
```

```php
final class StoreLeadRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name'    => 'required|string|max:120',
            'email'   => 'required|email|max:190',
            'phone'   => 'nullable|string|max:30',
            'message' => 'nullable|string|max:2000',
            'consent' => 'accepted',
            'website' => 'prohibited',          // honeypot
        ];
    }
}

final class ContactController extends Controller
{
    public function store(StoreLeadRequest $request): RedirectResponse|JsonResponse
    {
        $lead = Lead::create([
            ...$request->safe()->only('name', 'email', 'phone', 'message'),
            'source' => 'landing',
            'ip' => $request->ip(),
        ]);

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Obrigado!', 'id' => $lead->id], 201);
        }

        return redirect()->route('thanks')->with('success', 'Obrigado! Entramos em contacto em breve.');
    }
}
```

## 5. View

```blade
{{-- resources/views/home.blade.php --}}
@extends('layouts.app')
@section('title', 'Promo')

@section('content')
<section class="hero">
    <h1>Mais clientes para o teu negócio</h1>
    <p>Deixa o contacto e falamos hoje.</p>
</section>

<form method="POST" action="{{ route('contact.store') }}" class="lead-form">
    @csrf
    <label>Nome <input name="name" value="{{ old('name') }}" required></label>
    @error('name') <small class="error">{{ $message }}</small> @enderror

    <label>Email <input type="email" name="email" value="{{ old('email') }}" required></label>
    @error('email') <small class="error">{{ $message }}</small> @enderror

    <label>Telefone <input name="phone" value="{{ old('phone') }}"></label>
    <label>Mensagem <textarea name="message">{{ old('message') }}</textarea></label>

    <label class="consent"><input type="checkbox" name="consent" value="1" @checked(old('consent'))> Aceito a <a href="/privacidade">política de privacidade</a></label>
    @error('consent') <small class="error">{{ $message }}</small> @enderror

    <input name="website" class="hp" tabindex="-1" autocomplete="off">   {{-- honeypot, escondido por CSS --}}
    <button>Quero ser contactado</button>
</form>
@endsection
```

## 6. Teste

```php
it('grava um lead', function () {
    $this->post('/contact', ['name' => 'Ana', 'email' => 'ana@x.pt', 'consent' => '1'])
        ->assertRedirect('/obrigado');
    $this->assertDatabaseHas('leads', ['email' => 'ana@x.pt', 'status' => 'new']);
})->uses(RefreshDatabase::class);
```

## 7. Painel

```bash
composer require slendie/auth slendie/react-admin
php slendie auth:install && php slendie react-admin:install
npm install && npm run dev
```

Ou em Blade puro:

```php
Route::prefix('/admin')->middleware('auth')->group(function () {
    Route::get('/leads', fn (Request $r) => view('admin.leads', ['leads' => Lead::search($r->q)->latest()->paginate(20)]));
});
```

## 8. Deploy

Ver [deployment.md](deployment.md). `APP_DEBUG=false`, `php slendie migrate --force`, document root em `public/`.
