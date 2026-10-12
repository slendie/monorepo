# Request e Response

## Request

```php
use Slendie\Http\Request;

public function store(Request $request)
{
    $request->method();                  // HttpMethod::POST (já com _method spoofing); realMethod() é o do protocolo
    $request->isMethod('post');
    $request->path();                    // /contacto (sempre com "/" inicial, sem trailing slash nem query)
    $request->uri();                     // /contacto?x=1
    $request->url();  $request->fullUrl();  $request->root();   // http://host/contacto | ...?x=1 | http://host
    $request->ip();   $request->userAgent();
    $request->scheme();  $request->host();  $request->isSecure();

    $request->input('name');             // query + body + json (body vence a query)
    $request->input('name', 'default');
    $request->query('page', 1);          // só query string
    $request->post('x');                 // só body de formulário
    $request->json('items.0.id');        // body JSON com dot notation (descodificado só quando pedido)
    $request->all();  $request->only('a', 'b');  $request->except('_token');
    $request->has('x');  $request->filled('x');  $request->missing('x');
    $request->boolean('consent');  $request->integer('page', 1);  $request->string('q');

    $request->header('Accept');  $request->bearerToken();  $request->cookie('x');
    $request->expectsJson();  $request->wantsJson();  $request->isJson();  $request->ajax();  $request->isPrefetch();

    $request->file('avatar');            // UploadedFile|null
    $request->hasFile('avatar');  $request->allFiles();

    $request->route('lead');             // parâmetro de rota
    $request->route()->getName();
    $request->session()->get('x');       // lança RuntimeException fora do grupo "web"
    $request->user();                    // preenchido pelo package slendie/auth (setUserResolver)
    $request->attribute('tenant');       // dados passados entre middlewares: setAttribute('tenant', $t)
    $request->validate([...]);           // peça de validação (ver validation.md)
}
```

- `expectsJson()` é verdadeiro quando o `Accept` pede JSON, o pedido é XHR **ou** o caminho é `/api` ou `/api/*`.
- Em testes: `Request::create('/posts?page=2', 'POST', ['name' => 'Ana'], cookies: [...], server: [...], content: '{"a":1}')`.
  Em `GET`/`HEAD` os parâmetros vão para a query string; nos outros métodos para o corpo.
- `Request::capture()` cria o pedido a partir das superglobais (é o que `public/index.php` faz).

### Ficheiros

```php
$file = $request->file('avatar');
$file->isValid();  $file->clientName();  $file->extension();  $file->mimeType();  $file->size();
$path = $file->store('avatars');                    // storage/app/avatars/{random}.{ext}  → devolve 'avatars/xxxx.png'
$path = $file->store('avatars', 'x.png');           // com nome próprio
$path = $file->storePublicly('avatars', 'x.png');   // public/uploads/avatars/x.png  → devolve 'uploads/avatars/x.png'
```

Campos `name="docs[]"` dão uma lista de `UploadedFile` (`$request->file('docs.0')`).

## Response

Nada faz `echo` fora de `Response::send()`.

```php
return response('Olá', 200, ['X-Custom' => '1']);
return response()->json(['ok' => true], 201);
return response()->noContent();                     // 204
return response()->view('x', $data, 200);           // motor de views: peça 03
return response()->download(storage_path('app/f.pdf'), 'relatorio.pdf');

return response('x')->header('Cache-Control', 'no-store')->cookie('name', 'value', minutes: 60);
```

`Response`: `setContent()/getContent()`, `setStatusCode()/getStatusCode()`, `header()/withHeaders()/headers()`,
`cookie()`, `isOk()`, `isSuccessful()`, `isRedirect()`, `send()`. Pedidos `HEAD` e respostas `204`/`304` não levam corpo.

`JsonResponse` aceita `Arrayable`/`JsonSerializable`, usa `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` por defeito
(3.º argumento a seguir aos headers: `new JsonResponse($data, 200, [], $flags)`) e expõe `getData()`/`setData()`.

### Redirects

```php
return redirect('/obrigado');
return redirect()->route('thanks');
return redirect()->route('leads.show', ['lead' => $lead]);   // objectos com getRouteKey() servem de valor
return redirect()->to('/x', 301);
return redirect()->away('https://externo.com');
return back();                                                // Referer, ou o URL guardado na sessão, ou "/"
return back()->withInput()->withErrors($errors);
return redirect('/x')->with('success', 'Guardado!');          // flash
```

`with()`, `withInput()` e `withErrors()` precisam de sessão (grupo `web`). `withInput()` ignora `password`,
`password_confirmation` e `_token`. `withErrors()` aceita um array, uma `MessageBag` ou um `Validator` (e um nome de bag como 2.º argumento).
`redirect()->intended()` pertence ao package `slendie/auth`.

### Views

```php
return view('home', ['title' => 'Olá']);
```

Uma `Renderable` (ou um `ViewResponse`, `response()->view()`) devolvida pela acção é renderizada logo pelo `Router`, dentro do
pipeline: uma excepção na view — ou um `abort()` dentro dela — passa pelo `Handler`, e a sessão ainda está activa (`old()`,
flash, `csrf_token()`).

## Cookies

```php
return response('x')->cookie('name', 'value', minutes: 60, path: '/', domain: null, secure: false, httpOnly: true, sameSite: 'lax');
$response->cookie(cookie('name', 'value', 60));    // helper cookie(): secure automático em HTTPS
$request->cookie('name');
Cookie::forget('name');                            // Slendie\Http\Cookie: expira a cookie
```

Cookies de sessão são `HttpOnly`, `SameSite=Lax` e `Secure` quando o pedido é HTTPS. As rotas `web` também enviam a cookie
`XSRF-TOKEN` (legível por JavaScript) para frameworks front-end que a ecoam no header `X-XSRF-TOKEN`.

## Headers de segurança

O middleware global `SecurityHeaders` envia `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy` e
`Permissions-Policy`. Configura em `config/app.php` → `security_headers` (um valor vazio desactiva esse header; qualquer
header extra do array também é enviado).
