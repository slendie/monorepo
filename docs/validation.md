# Validação

## No controller

```php
public function store(Request $request): RedirectResponse
{
    $data = $request->validate([
        'name'    => 'required|string|max:120',
        'email'   => 'required|email|max:190',
        'phone'   => 'nullable|string|max:30',
        'message' => 'nullable|string|max:2000',
        'consent' => 'accepted',
        'website' => 'prohibited',                 // honeypot
    ]);

    Lead::create($data);                           // só os campos validados
    return redirect()->route('thanks');
}
```

Se falhar:
- pedido normal → `302` de volta com `$errors` e `old()` em sessão;
- pedido JSON/API → `422` com `{"message": "...", "errors": {"email": ["..."]}}`.

## FormRequest

```bash
php slendie make:request StoreLeadRequest
```

```php
final class StoreLeadRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return ['name' => ['required', 'string', 'max:120'], 'email' => ['required', 'email', Rule::unique('leads')]];
    }

    public function messages(): array { return ['email.unique' => 'Já recebemos este email.']; }
    public function attributes(): array { return ['email' => 'endereço de email']; }

    protected function prepareForValidation(): void
    {
        $this->merge(['email' => mb_strtolower((string) $this->input('email'))]);
    }
}

public function store(StoreLeadRequest $request)      // já validado quando chega aqui
{
    Lead::create($request->validated());
    $request->safe()->only(['name']);
}
```

## Manual

```php
$validator = Validator::make($data, $rules, $messages, $attributes);
if ($validator->fails()) { $errors = $validator->errors(); }
$validated = $validator->validate();                 // lança ValidationException
$validator->sometimes('phone', 'required', fn ($input) => $input->contact_by === 'phone');
```

## Regras disponíveis

**Presença:** `required`, `required_if:field,value`, `required_unless`, `required_with:a,b`, `required_with_all`,
`required_without`, `required_without_all`, `present`, `filled`, `nullable`, `sometimes`, `prohibited`, `prohibited_if`,
`missing`, `bail`, `exclude`, `exclude_if`, `exclude_unless`

**Tipo:** `string`, `numeric`, `integer`, `boolean`, `array`, `json`, `date`, `date_format:Y-m-d`, `email`, `url`, `ip`,
`ipv4`, `ipv6`, `uuid`, `alpha`, `alpha_num`, `alpha_dash`, `ascii`, `lowercase`, `uppercase`, `accepted`,
`accepted_if`, `declined`, `file`, `image`, `mimes:jpg,png`, `mimetypes:image/png`, `extensions:pdf`

**Tamanho** (string = caracteres, número = valor, array = elementos, ficheiro = KB): `min:`, `max:`, `size:`,
`between:a,b`, `digits:`, `digits_between:`, `gt/gte/lt/lte:campo|valor`, `multiple_of:`, `decimal:min,max`

**Formato/valores:** `regex:/…/`, `not_regex`, `starts_with:`, `ends_with:`, `doesnt_start_with:`, `contains:`,
`in:a,b`, `not_in:`, `in_array:outro.*`, `enum:App\Enums\Status`

**Datas:** `before:date|campo`, `after:`, `before_or_equal:`, `after_or_equal:`

**Relações:** `same:campo`, `different:campo`, `confirmed` (usa `{campo}_confirmation`), `distinct`,
`exists:table,column`, `unique:table,column,exceptId,idColumn`

**Objectos `Rule`:** `Rule::in([...])`, `Rule::notIn()`, `Rule::unique('users')->ignore($id)`, `Rule::exists('x', 'col')`,
`Rule::enum(Status::class)`, `Rule::requiredIf(fn () => ...)`, `Rule::when($cond, $rules)`,
`Password::min(8)->letters()->mixedCase()->numbers()->symbols()`, `File::types(['pdf'])->max(2048)`

**Closures:**
```php
'code' => [fn (string $attr, mixed $value, Closure $fail) => $value === 'X' || $fail('Código inválido.')]
```

**Wildcards:** `'items.*.qty' => 'required|integer|min:1'`

### Não suportadas (vs Laravel)
`active_url`, `dimensions`, `exists`/`unique` com closures `where`, `Password::uncompromised()`, `timezone`, `current_password`.

## Mensagens

Em `pt` e `en` por defeito (`config('app.locale')`). Para alterar:

```bash
php slendie vendor:publish --tag=slendie-lang      # cria lang/pt/validation.php
```

Ou por chamada: `$request->validate($rules, ['email.required' => 'Precisamos do email.'], ['email' => 'email']);`

## Nas views

```blade
<input name="email" value="{{ old('email') }}">
@error('email') <span>{{ $message }}</span> @enderror

@if ($errors->any()) … @endif
{{ $errors->first('email') }}
@foreach ($errors->get('items.*') as $msg) … @endforeach
```

`$errors` é um `MessageBag`: `has()`, `first()`, `get()`, `all()`, `any()`, `count()`, `toArray()`.
