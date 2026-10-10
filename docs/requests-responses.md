# Request e Response

## Request

```php
use Slendie\Http\Request;

public function store(Request $request)
{
    $request->method();                  // HttpMethod::POST (já com _method spoofing)
    $request->path();                    // /contacto
    $request->url();  $request->fullUrl();
    $request->ip();   $request->userAgent();
    $request->isSecure();

    $request->input('name');             // query + body + json (body vence)
    $request->input('name', 'default');
    $request->query('page', 1);          // só query string
    $request->post('x');                 // só body
    $request->json('items.0.id');        // body JSON com dot notation
    $request->all();  $request->only(['a', 'b']);  $request->except(['_token']);
    $request->has('x');  $request->filled('x');  $request->missing('x');
    $request->boolean('consent');  $request->integer('page', 1);  $request->string('q');

    $request->header('Accept');  $request->bearerToken();  $request->cookie('x');
    $request->expectsJson();  $request->wantsJson();  $request->isJson();  $request->ajax();

    $request->file('avatar');            // UploadedFile|null
    $request->hasFile('avatar');

    $request->route('lead');             // parâmetro de rota
    $request->route()->getName();
    $request->session()->get('x');
    $request->user();                    // com slendie/auth
    $request->validate([...]);           // ver validation.md
}
```

### Ficheiros

```php
$file = $request->file('avatar');
$file->isValid();  $file->clientName();  $file->extension();  $file->mimeType();  $file->size();
$path = $file->store('avatars');                    // storage/app/avatars/{random}.{ext}
$path = $file->storePublicly('avatars', 'x.png');   // public/uploads/avatars/x.png
```

## Response

```php
return response('Olá', 200, ['X-Custom' => '1']);
return response()->json(['ok' => true], 201);
return response()->noContent();                     // 204
return response()->view('x', $data, 200);
return response()->download(storage_path('app/f.pdf'), 'relatorio.pdf');
return response()->file($path);

return response('x')->header('Cache-Control', 'no-store')->cookie('name', 'value', minutes: 60);
```

### Redirects

```php
return redirect('/obrigado');
return redirect()->route('thanks');
return redirect()->route('leads.show', ['lead' => $lead]);   // aceita Model (usa a chave)
return redirect()->to('/x', 301);
return redirect()->away('https://externo.com');
return back();
return back()->withInput()->withErrors($errors);
return redirect('/x')->with('success', 'Guardado!');          // flash
return redirect()->intended('/admin');                       // com slendie/auth
```

### Views

```php
return view('home', ['title' => 'Olá']);
return view('home')->with('title', 'Olá');
```

## Cookies

```php
cookie('name', 'value', minutes: 60, path: '/', secure: null, httpOnly: true, sameSite: 'lax');
$response->cookie(...);
$request->cookie('name');
```

Cookies de sessão são `HttpOnly`, `SameSite=Lax` e `Secure` quando o pedido é HTTPS.

## Headers de segurança

O middleware global `SecurityHeaders` envia `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` e
`Permissions-Policy`. Configura em `config/app.php` → `security_headers`.
