# core-04-05 — Model (Eloquent light)

> Peça 05 · Depende de: 02 (UrlRoutable, SubstituteBindings), 04 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Base de dados e Model), análise crítica §4.2

## Objectivo
Um `Model` com objectos hidratados, mass-assignment protegido, casts, timestamps, soft deletes opcional, relações
lazy `hasOne/hasMany/belongsTo`, `Collection`, e proxies estáticos para o Query Builder com os sinónimos
`fetch()/first()` e `fetchAll()/get()`. Route-model binding automático.

## Ficheiros a criar (`framework/src/Model/`)
- `Model.php` (abstract) implements `Arrayable, JsonSerializable, ArrayAccess, UrlRoutable, Stringable`
  - propriedades configuráveis (todas `protected`, com defaults):
    `?string $table = null` (default `Str::snake(Str::plural(class_basename(static::class)))`), `string $primaryKey = 'id'`,
    `string $keyType = 'int'`, `bool $incrementing = true`, `array $fillable = []`, `array $guarded = ['*']`
    (se `$fillable` vazio e `$guarded === ['*']` → tudo bloqueado, como Laravel), `array $casts = []`, `array $hidden = []`,
    `array $visible = []`, `array $appends = []`, `bool $timestamps = true`, `const CREATED_AT = 'created_at'`,
    `const UPDATED_AT = 'updated_at'`, `string $dateFormat = 'Y-m-d H:i:s'`, `?string $connection = null`, `array $with = []` (ignorado; eager loading fora de âmbito — documentar), `array $attributes = []` (defaults)
  - estado: `$attributes`, `$original`, `$relations`, `$exists`, `$wasRecentlyCreated`
  - acesso: `__get/__set/__isset/__unset` → `getAttribute(key)` (atributo → cast → accessor `get{Studly}Attribute` → relação lazy se método existir → `null`) e `setAttribute(key, value)` (mutator `set{Studly}Attribute` → cast inverso → atributo). Suporte a **property hooks do PHP 8.5 não** é usado aqui (atributos são dinâmicos); mencionar em docs
  - casts suportados: `int|integer`, `float|double`, `string`, `bool|boolean`, `array|json` (json_encode/decode assoc), `object`, `collection` (Collection), `date` (DateTimeImmutable à meia-noite), `datetime` (DateTimeImmutable), `immutable_datetime` (igual), `timestamp` (int), `decimal:2`, `encrypted` **não**, enums (`EnumClass::class` BackedEnum → from/value)
  - `fill(array): static` (respeita fillable/guarded; chave não permitida → `MassAssignmentException` quando `Model::preventSilentlyDiscardingAttributes()`, senão descarta em silêncio — default: lançar em `APP_DEBUG=true`, descartar em produção), `forceFill()`, `isFillable(key)`, `getFillable()`, `guard()`, `unguard()/reguard()` estáticos
  - `getKey()`, `getKeyName()`, `getTable()`, `getConnectionName()`, `getConnection(): Connection`, `usesTimestamps()`
  - `getAttributes()`, `getOriginal(?key)`, `getDirty()`, `isDirty(?key|array)`, `isClean()`, `wasChanged()`, `syncOriginal()`, `only()`, `except()`
  - `toArray()` (atributos castados + relações carregadas, aplicando hidden/visible/appends com accessors), `toJson(flags)`, `jsonSerialize()`, `__toString` = toJson, `makeHidden/makeVisible`
  - persistência: `save(array $options = []): bool` (insert vs update por `$exists`; só dirty; timestamps; `insertGetId` quando incrementing; hooks `creating/created/updating/updated/saving/saved/deleting/deleted` via métodos estáticos `static::creating(Closure)` com registo por classe num `ModelEvents` estático simples — devolver `false` no `*ing` cancela), `update(array)`, `delete(): ?bool`, `forceDelete()`, `refresh()`, `fresh()`, `replicate(except = [])`, `touch()`, `is(?Model)`, `isNot()`
  - estáticos: `create(array)`, `forceCreate()`, `make()`, `find(id|array)`, `findOrFail(id)` (→ `ModelNotFoundException` com classe e ids; `Handler` converte em 404), `findMany()`, `all(cols)`, `first()/fetch()`, `firstWhere()`, `firstOrCreate(attrs, values)`, `firstOrNew()`, `updateOrCreate()`, `destroy(ids)`, `count()`, `query(): Builder`, `on(connection)`
  - `__call/__callStatic` → encaminha para `newQuery()` (permite `Lead::where()`, `Lead::whereLike()`, `Lead::orderBy()`, `Lead::paginate()`, scopes)
  - **scopes locais**: método `scopeActive(Builder $q, ...$args)` → `Lead::active()`; **global scopes**: `static::addGlobalScope(string $name, Closure)`; `withoutGlobalScope(s)()`
  - relações (devolvem `Relation`): `hasOne(related, foreignKey = null, localKey = null)`, `hasMany(...)`, `belongsTo(related, foreignKey = null, ownerKey = null)`; convenções: FK `{snake(class)}_id`; `getRelationValue(key)` carrega lazy e guarda em `$relations`; `load(string|array)` (lazy load explícito, 1 query por relação por model — **sem** eager batching; documentar limitação e sugerir `whereIn` manual), `setRelation`, `relationLoaded()`, `unsetRelation`
  - `UrlRoutable`: `getRouteKey()` (`getKey()`), `getRouteKeyName()` (`primaryKey`; override para slug), `resolveRouteBinding(value, ?field): ?static` (`where(field ?? routeKeyName, value)->first()`)
  - `newQuery(): Builder`, `newModelQuery()`, `newInstance(attrs, exists = false)`, `newFromBuilder(array $row)` (hidratação sem fill), `newCollection(array $models): Collection`
  - `SoftDeletes` trait (`deleted_at`, scope global `whereNull`, `withTrashed()`, `onlyTrashed()`, `restore()`, `trashed()`, `forceDelete()`), `HasUuids` trait (`primaryKey` string, `Str::uuid()` em creating, `incrementing=false`)
- `Builder.php` — envolve `Query\Builder` (composição + `__call` passthrough para métodos de where/order/etc. devolvendo `$this`):
  - `get(cols): Collection<Model>`, `fetchAll()` = `get()`, `first(): ?Model`, `fetch()` = `first()`, `firstOrFail()`, `find/findOrFail/findMany`, `pluck`, `value`, `count/sum/...` (passthrough), `exists`, `paginate(): Paginator<Model>`, `chunk`, `cursor`, `update(array)` (aplica `updated_at`), `delete()` (respeita SoftDeletes), `insert` passthrough, `toSql/getBindings`, `getModel()`, `setModel()`, `getQuery()`, `with()` → lança `LogicException('Eager loading fora de âmbito na v4.0; use ->load()')`, `withTrashed/onlyTrashed` via scopes, `applyScopes()`, `scopes()`, `__call` resolve `scope*` do model
- `Collection.php` implements `ArrayAccess, IteratorAggregate, Countable, JsonSerializable, Arrayable` — construtor `array`, `all()`, `toArray()` (chama `toArray` em cada item Arrayable), `toJson()`, `map/filter/reject/each/first(callback?)/last/pluck(key, ?indexBy)/keyBy/groupBy/sortBy/sortByDesc/sortKeys/reverse/values/keys/count/isEmpty/isNotEmpty/sum/avg/min/max/contains/where(key, op?, val)/whereIn/unique/take/slice/chunk/merge/push/put/pull/get(key, default)/has/implode/only/except/find(key)/modelKeys/load(relations)/fresh()/random()/shuffle()`; macros não
- `Relations/Relation.php` (abstract) — `__construct(Builder $query, Model $parent)`, `getResults()`, `getQuery()`, passthrough `__call` para o Builder (permite `$user->posts()->where(...)->get()`)
- `Relations/HasOne.php`, `HasMany.php` (`getResults` → `first()`/`get()`; `create(attrs)`, `save(Model)`, `saveMany`, `make`), `BelongsTo.php` (`getResults` → `first()`; `associate(Model)`, `dissociate()`)
- `ModelNotFoundException` (`setModel(class, ids)`), `MassAssignmentException`, `RelationNotFoundException`
- `Http/Middleware/SubstituteBindings.php` (peça 02) — completar: parâmetro de rota tipado com subclasse de `Model` → `resolveRouteBinding`; `null` → `ModelNotFoundException`; suporte a `{post:slug}` (field explícito)
- `Foundation/Exceptions/Handler.php` — mapear `ModelNotFoundException` → 404
- `Support/Facades/DB` já existe; nada de novo
- Stubs para `make:model` ficam na peça 07; `Str::plural` já existe (01)

## Testes (Pest, SQLite `:memory:`, tabelas criadas por SQL cru no `beforeEach`)
- Nome de tabela por convenção (`Lead` → `leads`, `Category` → `categories`, `Person` → `people`); override `$table`.
- `fill` respeita fillable; guarded lança em debug / descarta em produção; `forceFill`.
- Casts: cada tipo acima, round-trip (set → DB → get), enum, json.
- Accessor/mutator; `appends`; `hidden/visible` em `toArray/toJson`.
- `create/save/update/delete/refresh/fresh/replicate`; dirty tracking; só colunas dirty vão no UPDATE (verificar via query log); timestamps criados/actualizados; `$timestamps=false`.
- Eventos: `creating` que devolve `false` cancela; `saved` corre após insert e update.
- Estáticos e `__callStatic`: `Lead::where('email', $e)->fetch()`, `Lead::whereLike('name', 'SILVA')->fetchAll()` (case-insensitive em SQLite ASCII), `first()/get()` equivalentes, `find`, `findOrFail` lança, `all`, `firstOrCreate`, `updateOrCreate`, `destroy`, `paginate` devolve `Paginator` com `Model`s, scopes locais e globais, `SoftDeletes` (delete marca, `withTrashed`, `restore`), `HasUuids`.
- Relações: `hasMany/belongsTo/hasOne` com FKs por convenção e explícitas; lazy via propriedade e via método com constraints; `create()` via relação; `associate/dissociate`; `load()`; `relationLoaded`.
- Route-model binding: rota `/leads/{lead}` com `Lead $lead` → instância; inexistente → 404; `{lead:slug}`.
- Collection: 15 métodos mais usados + `toJson` com hidden aplicados.

## Critérios de aceitação
- Exemplo do brief corre tal e qual: `$clientes->where('client_nif', '123456789')->fetch()` **e** `Cliente::where(...)->fetch()` (instância usa `__call` → `newQuery()`), `$clientes->whereLike('client_name', 'SILVA')->fetchAll()`.
- `composer test/analyse/lint` verdes; `docs/models.md` escrito (incl. secção "Diferenças para o Eloquent": sem eager loading `with()`, sem many-to-many, sem observers em classe, sem factories).

## Não fazer nesta peça
Eager loading, `belongsToMany`, polimórficas, factories, `Model::preventLazyLoading`, attribute classes (`Attribute::make`), pruning.

## Desvios
Implementado em 2026-10-11 (754 testes no total, 70 skipped por falta de MySQL/PostgreSQL locais; PHPStan nível 8 e Pint verdes). Divergências face ao texto acima:

1. **`Route` ganhou `{param:campo}`** (`bindingField()`): o router e o `UrlGenerator` aceitam `/posts/{post:slug}` e o `SubstituteBindings` passa o campo a `resolveRouteBinding()`. Era o que faltava da peça 02 para o binding por campo.
2. **`SubstituteBindings`**: para subclasses de `Model` lança `ModelNotFoundException` (→ 404 via `Handler`); para outros `UrlRoutable` mantém `NotFoundHttpException`. `ModelNotFoundException` estende `RecordNotFoundException`, por isso o `Handler` não precisou de mapeamento novo (só um comentário).
3. **Ligação do model**: `Model::setConnectionResolver()` (closure ou `DatabaseManager`), ligado pelo `DatabaseServiceProvider::boot()`; sem ele, usa o `DatabaseManager` do container ou lança `LogicException`.
4. **`Query\Builder::groupWheres()`** (novo, público): os global scopes são aplicados sobre os `where` existentes agrupados em parênteses, para que `where(a)->orWhere(b)` não se misture com `deleted_at is null`.
5. **Relações** têm de declarar o tipo de retorno (`HasMany`, `HasOne`, `BelongsTo`); é assim que `$lead->notes` distingue relações de outros métodos sem invocar métodos arbitrários. `belongsTo` usa o nome da classe relacionada para a FK (`Lead` → `lead_id`), não o nome do método. Criou-se `HasOneOrMany` como base comum e as relações acrescentam `whereNotNull(fk)`.
6. **Mass-assignment em debug**: a decisão lê `Application::isDebug()` do container; sem aplicação, descarta. `Model::preventSilentlyDiscardingAttributes(true|false|null)` força o comportamento.
7. **Serialização**: em `toArray()` as datas saem como texto (`$dateFormat`, ou `Y-m-d` para `date`), não ISO 8601; os enums como o seu valor.
8. **Casts**: `created_at`/`updated_at` são `datetime` por omissão e a chave primária incremental é `int`; `encrypted` e casts desconhecidos lançam `LogicException` ao aceder/atribuir.
9. **Builder do model**: `pluck()`/`value()` devolvem valores em bruto (sem casts); os closures de `where(fn ($q) => …)` recebem o Query Builder base. `update()`/`delete()`/`restore()` em massa não disparam eventos. `Model::create/forceCreate` constroem a instância directamente (equivalente a `query()->create()`); `firstOrCreate/firstOrNew/updateOrCreate/firstWhere/find…` chegam aos estáticos via `__callStatic` (documentados com `@method`).
10. **Extras**: `Builder::restore()`, `simplePaginate()`, `Model::clearBootedModels()` (testes), `getChanges()`, `Collection::__toString/random/shuffle`, `Model::on()`.
11. **PHPStan**: `@phpstan-consistent-constructor` em `Model`, `Collection` e `RelationNotFoundException`; `phpstan.neon` ignora `trait.unused` para `SoftDeletes`/`HasUuids` (só são usadas por models de aplicações e fixtures).
12. **Não executado**: o `Model` só foi testado em SQLite `:memory:`; MySQL/PostgreSQL dependem do SQL do Query Builder (peça 04, também não executado nesses drivers). Stubs `make:model` continuam na peça 07.
