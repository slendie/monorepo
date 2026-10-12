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
$validator->sometimes('phone', 'required', fn (array $input) => ($input['contact_by'] ?? null) === 'phone');
$validator->after(fn (Validator $v) => $v->errors()->add('geral', 'Algo não bate certo.'));
$validator->stopOnFirstFailure();
```

## Regras disponíveis

**Presença:** `required`, `required_if:field,value`, `required_unless`, `required_with:a,b`, `required_with_all`,
`required_without`, `required_without_all`, `present`, `filled`, `nullable`, `sometimes`, `prohibited`, `prohibited_if`,
`prohibited_unless`, `missing`, `missing_if`, `missing_unless`, `missing_with`, `missing_with_all`, `accepted_if`, `bail`,
`exclude`, `exclude_if`, `exclude_unless`, `exclude_with`, `exclude_without` (o campo deixa de ser validado e sai de `validated()`)

**Tipo:** `string`, `numeric`, `integer`, `boolean`, `array` (`array:a,b` limita as chaves), `json`, `date`,
`date_format:Y-m-d`, `email` (`email:dns` verifica o MX), `url` (`url:https`), `ip`, `ipv4`, `ipv6`, `uuid`, `alpha`,
`alpha_num`, `alpha_dash` (os três aceitam `:ascii`), `ascii`, `lowercase`, `uppercase`, `timezone`, `hex_color`, `accepted`,
`accepted_if`, `declined`, `declined_if`, `file`, `image` (SVG só com `image:allow_svg`), `mimes:jpg,png`,
`mimetypes:image/png` (aceita `image/*`), `extensions:pdf`. Os tipos de ficheiro são detectados pelo conteúdo (`finfo`), não pelo nome.

**Tamanho** (string = caracteres, número = valor, array = elementos, ficheiro = KB): `min:`, `max:`, `size:`,
`between:a,b`, `digits:`, `digits_between:`, `gt/gte/lt/lte:campo|valor`, `multiple_of:`, `decimal:min,max`

**Formato/valores:** `regex:/…/`, `not_regex`, `starts_with:`, `ends_with:`, `doesnt_start_with:`, `contains:`,
`in:a,b`, `not_in:`, `in_array:outro.*`, `enum:App\Enums\Status`

**Datas:** `before:date|campo`, `after:`, `before_or_equal:`, `after_or_equal:`

**Relações:** `same:campo`, `different:campo`, `confirmed` (usa `{campo}_confirmation`), `distinct`,
`exists:table,column`, `unique:table,column,exceptId,idColumn`

**Objectos `Rule`:** `Rule::in([...])`, `Rule::notIn()`, `Rule::unique('users')->ignore($id)`, `Rule::exists('x', 'col')`,
`Rule::enum(Status::class)` (`->only([...])`, `->except([...])`), `Rule::requiredIf(fn () => ...)`, `Rule::prohibitedIf(...)`,
`Rule::when($cond, $rules, $default)` (`$cond` pode ser `fn (array $data): bool`),
`Password::min(8)->letters()->mixedCase()->numbers()->symbols()` (também `->max()`; `Password::defaults(fn () => ...)` e `Password::default()`),
`Rule::file()->types(['pdf'])->max(2048)` (também `->image()`, `->min()`, `->size()`, `->between()`).

`Rule::unique`/`Rule::exists` aceitam `->where('coluna', valor)`, `->where(fn ($query) => ...)`, `->whereNot()`, `->whereNull()`,
`->whereNotNull()`, `->column('x')`; `unique` acrescenta `->ignore($id, 'id')` (ou um model) e `->withoutTrashed()`.
Em texto: `unique:users,email,5,id,account_id,3` (pares `coluna,valor` extra; `NULL` = `IS NULL`, `!x` = diferente de x).
Interface para regras próprias: `Slendie\Validation\ValidationRule` (`validate($attr, $value, Closure $fail)`); com `DataAwareRule` /
`ValidatorAwareRule` recebem os dados e o validador. Uma regra com `public bool $implicit = true` corre mesmo com o campo vazio.

**Regras por nome:** `Validator::extend('par', fn ($attr, $value, $params, $validator) => $value % 2 === 0, 'O :attribute tem de ser par.')`.

**Closures:**
```php
'code' => [fn (string $attr, mixed $value, Closure $fail) => $value === 'X' || $fail('Código inválido.')]
```

**Wildcards:** `'items.*.qty' => 'required|integer|min:1'`

### Não suportadas (vs Laravel)
`active_url`, `dimensions`, `Password::uncompromised()`, `current_password`, `exclude_*` com closure (`Rule::excludeIf()`),
`exists`/`unique` com o nome de um model ou de uma ligação (`connection.table`), `prohibits`, `required_if_accepted`,
`present_if`/`present_unless`, `list`, `ulid`, `mac_address`, `max_digits`/`min_digits`, `encoding`, `any_of`. Usar uma regra
inexistente ou não suportada lança `BadMethodCallException` (nunca passa em silêncio).

### Comportamento a conhecer
- Um campo **ausente ou vazio** só é validado por regras que o exigem (`required*`, `present`, `filled`, `accepted*`, `prohibited*`, `missing*`).
  Com `null` (por exemplo, strings vazias convertidas pelo `TrimStrings`) as restantes regras falham — usa `nullable` nos campos opcionais.
- Se uma regra de presença falha (`required`...), as seguintes não correm: o campo tem uma só mensagem. `bail` pára na primeira falha de qualquer regra.
- Sem sessão (grupo `api`, `/api/*` ou `Accept: application/json`) a falha responde `422` com `{"message", "errors"}`; num pedido web
  redirecciona de volta com `$errors` e `old()` (a `password`, `password_confirmation`, `current_password` e `_token` nunca são repostas).

## Mensagens

Em `pt` e `en` por defeito (`config('app.locale')`; ver [Traduções](localization.md)). Para alterar:

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
