# core-04-08 — Validação e formulários

> Peça 08 · Depende de: 02, 03 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Controllers), caso de uso formulário de contacto

## Objectivo
`$request->validate([...])` com regras estilo Laravel, mensagens em pt-PT e en, redirect automático de volta com
erros e input antigo, `$errors` nas views, `@error`, `old()`, `FormRequest` opcional. É o que transforma a landing
page num formulário de leads a sério.

## Ficheiros a criar (`framework/src/Validation/`)
- `Validator.php`
  - `__construct(array $data, array $rules, array $messages = [], array $attributes = [], Translator $translator)`
  - regras aceitam `'required|email|max:255'`, `['required', 'email']`, objectos `Rule`, closures `fn ($attr, $value, Closure $fail)`
  - atributos aninhados com dot e wildcard: `items.*.qty`, `address.city`
  - `passes(): bool`, `fails()`, `validate(): array` (lança `ValidationException`), `validated(): array` (só chaves com regra, respeitando `exclude*`), `safe()` (object com `only/except/all`), `errors(): MessageBag`, `getMessageBag()`, `addFailure()`, `sometimes(attr, rules, Closure $condition)`, `after(Closure)`, `stopOnFirstFailure()`, `setCustomMessages()`, `setAttributeNames()`
  - implícitas (correm mesmo com valor vazio): `required`, `required_if`, `required_unless`, `required_with`, `required_with_all`, `required_without`, `required_without_all`, `present`, `filled`, `nullable`, `sometimes`, `accepted`, `accepted_if`, `declined`, `prohibited`, `prohibited_if`
  - tipo: `string`, `numeric`, `integer`, `boolean`, `array`, `json`, `date`, `date_format:F`, `before:date|field`, `after:`, `before_or_equal`, `after_or_equal`, `email` (filter_var + opcional `dns`), `url`, `active_url` **não**, `ip`, `ipv4`, `ipv6`, `uuid`, `alpha`, `alpha_num`, `alpha_dash`, `ascii`, `lowercase`, `uppercase`, `regex:/…/`, `not_regex`, `starts_with:a,b`, `ends_with`, `doesnt_start_with`, `contains`, `in:a,b`, `not_in`, `in_array:other.*`, `enum:Class`, `file`, `image` (mime por `finfo`), `mimes:jpg,png`, `mimetypes:`, `extensions:`, `max_file_size` → usar `max` em KB para ficheiros como o Laravel
  - tamanho (contextual por tipo: string chars `mb_strlen`, numeric valor, array count, file KB): `min:`, `max:`, `size:`, `between:`, `digits:`, `digits_between:`, `gt/gte/lt/lte:field|value`, `multiple_of:`, `decimal:min,max`
  - relação: `same:field`, `different:field`, `confirmed` (`{field}_confirmation`), `distinct`, `exists:table,column` e `unique:table,column,except,idColumn` (via `DatabaseManager`; `Rule::unique('users')->ignore($id)` fluente), `exclude`, `exclude_if`, `exclude_unless`, `exclude_with`, `exclude_without`, `missing*`, `bail`
  - `Rules/` — uma classe por regra complexa: `Unique`, `Exists`, `In`, `NotIn`, `Enum`, `Dimensions` **não**, `Password` (min, `letters()`, `mixedCase()`, `numbers()`, `symbols()`, `uncompromised()` **não**), `RequiredIf`, `ProhibitedIf`, `File` (`types()`, `max()`); interface `Rule` com `validate(string $attribute, mixed $value, Closure $fail): void`; `Rule::in()`, `Rule::unique()`, `Rule::exists()`, `Rule::enum()`, `Rule::requiredIf()`, `Rule::when()`
  - `ValidationRuleParser` — explode strings, parâmetros, wildcards
- `MessageBag.php` implements `Arrayable, Countable, JsonSerializable` — `add(key, message)`, `merge()`, `has(key|array)`, `hasAny()`, `first(?key, format = ':message')`, `get(key, format)`, `all(format)`, `keys()`, `isEmpty/isNotEmpty/any`, `count`, `toArray`, `toJson`, `getMessages()`; suporte a wildcards em `has/get` (`items.*`)
- `ValidationException.php` — `validator`, `errors(): array`, `status = 422`, `redirectTo`, `errorBag = 'default'`, `static withMessages(array)`; `Handler` renderiza: `expectsJson` → 422 `{message: 'Os dados fornecidos são inválidos.', errors: {...}}`; senão `redirect()->back()->withInput($request->except(password, password_confirmation))->withErrors($errors)`
- `Translator.php` (mínimo) — `get(key, replace = [], ?locale)`, `has()`, `choice()` **simples** (`{0} none|{1} one|[2,*] many`), `setLocale/getLocale`, `addLines(array, locale)`, `addNamespace/addPath` para packages; carrega `framework/lang/{locale}/validation.php` + `{basePath}/lang/{locale}/*.php` (projecto sobrepõe); `config('app.locale', 'pt')`, `fallback_locale: 'en'`; helper `__('validation.required', [...])` e `trans()`; `lang_path()`
  - `framework/lang/pt/validation.php` (pt-PT, todas as regras, `attributes`, `values`) e `framework/lang/en/validation.php`
  - `framework/lang/pt/pagination.php` (`previous`, `next`) e `en`
- `ValidationServiceProvider.php` — regista `Factory`? Manter simples: singleton `Translator`, factory via `Validator::make(data, rules, messages, attributes)` estático que resolve `Translator` e `DatabaseManager` do container; `Request::validate(rules, messages, attributes): array` e `Request::validateWithBag()` **não**; `Request::validator()`; facade `Support\Facades\Validator`
- `Http/FormRequest.php` (abstract, estende `Request`) — `rules(): array`, `messages()`, `attributes()`, `authorize(): bool` (false → 403), `prepareForValidation()`, `passedValidation()`, `validated()`, `safe()`, `after()`; resolvida pelo container na injecção em controllers (`SubstituteBindings`/`Container::call` detecta subclasse de `FormRequest` → cria a partir do `Request` corrente, valida, injecta) — implementar o gancho em `Routing/Router::runRoute()` via `Container::call` com resolver customizado (`Container::resolving(FormRequest::class, …)`)
- `Console/Commands/MakeRequestCommand` + `stubs/request.stub` (`make:request StoreLeadRequest`)
- `Http/Middleware/ShareErrorsFromSession` — passa a partilhar `MessageBag` real (`ViewErrorBag` **não**; um só bag: `$errors` é `MessageBag`); `View\Compiler` `@error` já compatível
- `Http/RedirectResponse::withErrors(MessageBag|array|Validator, $key = 'default')` — guarda em flash `errors` como `MessageBag`
- `Support/helpers.php` — `old(key, default)` lê `session()->getOldInput()`, `__()`, `trans()`, `trans_choice()`, `validator(data, rules, …)`, `lang_path()`
- Blade: `@error('name', 'bag')` ignora bag; `@errors`? **não**; mas directiva `@csrf` já existe

## Skeleton (apenas o necessário)
- `skeleton/lang/pt/validation.php` **não** copiado por defeito (framework fornece; `vendor:publish --tag=slendie-lang` publica para sobrepor)
- `skeleton/app/Http/Controllers/ContactController.php@store` usa `$request->validate([...])`; view `contact.blade.php` com `@error` e `old()` (a view completa é da peça 11; aqui só o controller e um teste)

## Testes (Pest)
- Uma tabela de casos por regra (data válida/inválida → passes/fails + mensagem pt e en) para **todas** as regras listadas; wildcards `items.*.qty`; `bail`; `sometimes`; `nullable` com `''`; `confirmed`; `unique/exists` com SQLite memória (ignore id); `Rule::in` com enum; closure rule; `Password` builder; `size` contextual (string/numeric/array/file com `UploadedFile` fake); `date_format`, `before/after` com campos; `exclude_if` remove de `validated()`; mensagens custom e nomes de atributos; `MessageBag` API completa; `Translator` `choice` e fallback locale e override pelo projecto.
- Feature: POST inválido em rota `web` → 302 back com `errors` e `old` em sessão; GET seguinte: view com `@error` mostra mensagem e `old('name')` repõe; POST inválido com `Accept: application/json` → 422 JSON com formato exacto; `FormRequest` injectado valida antes do controller; `authorize() false` → 403; `prepareForValidation` altera input.

## Critérios de aceitação
- Formulário de contacto do skeleton valida `name required|string|max:120`, `email required|email|max:190`, `phone nullable|string|max:30`, `message nullable|string|max:2000`, `website prohibited` (honeypot) e repõe valores.
- `composer test/analyse/lint` verdes; `docs/validation.md` com tabela de regras disponíveis **e** de regras do Laravel não suportadas (`active_url`, `dimensions`, `exclude_*` parcial, `uncompromised`), `docs/localization.md` curto.

## Desvios
(preencher na implementação)
