# Models

```bash
php slendie make:model Lead -m          # + migração
php slendie make:model Lead -mcr        # + migração + controller resource
```

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Slendie\Model\Model;

final class Lead extends Model
{
    protected ?string $table = 'leads';              // opcional: default snake plural (Lead → leads)
    protected array $fillable = ['name', 'email', 'phone', 'message', 'status'];
    protected array $casts = ['created_at' => 'datetime', 'meta' => 'json', 'status' => Status::class];
    protected array $hidden = ['ip'];
    protected array $attributes = ['status' => 'new'];   // defaults
}
```

## Ler

```php
Lead::all();
Lead::find(3);  Lead::findOrFail(3);        // 404 automático
Lead::findMany([1, 2]);
Lead::where('email', $email)->fetch();      // = ->first()
Lead::whereLike('name', 'SILVA')->fetchAll();   // = ->get() → Collection<Lead>
Lead::where('status', 'new')->latest()->paginate(20);
Lead::count();  Lead::where(...)->exists();
Lead::firstWhere('email', $e);
Lead::query()->...;                          // builder explícito
```

Como no brief, também funciona em instância:

```php
$clientes = new Cliente();
$clientes->where('client_nif', '123456789')->fetch();
$clientes->whereLike('client_name', 'SILVA')->fetchAll();
```

Todos os métodos do [Query Builder](query-builder.md) estão disponíveis e devolvem models hidratados.

## Escrever

```php
$lead = Lead::create(['name' => 'Ana', 'email' => 'ana@x.pt']);     // respeita fillable
$lead = new Lead(['name' => 'Ana']);  $lead->email = 'ana@x.pt';  $lead->save();
$lead->update(['status' => 'won']);
$lead->fill([...])->save();
$lead->delete();  Lead::destroy([1, 2]);
Lead::firstOrCreate(['email' => $e], ['name' => 'Ana']);
Lead::updateOrCreate(['email' => $e], ['name' => 'Ana']);
$lead->refresh();  $lead->fresh();  $lead->replicate();
$lead->isDirty('status');  $lead->getOriginal('status');  $lead->wasChanged();
$lead->exists;  $lead->wasRecentlyCreated;
```

Mass-assignment: chaves fora de `$fillable` lançam `MassAssignmentException` em debug (`APP_DEBUG=true`) e são
descartadas em produção; `Model::preventSilentlyDiscardingAttributes(true|false)` força um dos comportamentos.
`forceFill()` e `Model::unguard()` ignoram a protecção. Com `$fillable = []` e `$guarded = ['*']` (o default) tudo fica
bloqueado, como no Laravel.

Escritas em massa (`Lead::where(...)->update([...])`, `->delete()`, `->restore()`) vão directas à base de dados:
actualizam `updated_at`, mas **não** disparam eventos.

## Timestamps e casts

`created_at`/`updated_at` automáticos (`$timestamps = false` para desligar).

Casts: `int`, `float`, `string`, `bool`, `array`/`json`, `object`, `collection`, `date`, `datetime`, `timestamp`,
`decimal:2`, enums (`Status::class`). `created_at`/`updated_at` são `datetime` por omissão. Um cast desconhecido
(ex.: `encrypted`) lança `LogicException`.

Em `toArray()`/JSON as datas saem como texto (`$dateFormat`, ou `Y-m-d` nos casts `date`), os enums como o seu valor.

Os atributos são acedidos dinamicamente (`$lead->name`); as *property hooks* do PHP 8.5 não são usadas aqui.

## Accessors, mutators, appends

```php
public function getInitialsAttribute(): string { return mb_substr($this->name, 0, 1); }
public function setEmailAttribute(string $v): void { $this->attributes['email'] = mb_strtolower($v); }
protected array $appends = ['initials'];          // entra em toArray()/JSON
```

## Scopes

```php
public function scopeStatus(Builder $q, string $s): Builder { return $q->where('status', $s); }
public function scopeSearch(Builder $q, ?string $term): Builder
{
    return $q->when($term, fn ($q) => $q->whereLike('name', $term)->orWhereLike('email', $term));
}

Lead::status('new')->search($q)->paginate();

static::addGlobalScope('active', fn (Builder $q) => $q->where('active', true));
Lead::withoutGlobalScope('active')->get();
```

## Relações

```php
final class Lead extends Model
{
    public function user(): BelongsTo { return $this->belongsTo(User::class); }         // user_id
    public function notes(): HasMany { return $this->hasMany(Note::class); }           // notes.lead_id
    public function address(): HasOne { return $this->hasOne(Address::class, 'lead_id'); }
}

$lead->user;                      // lazy, cacheado
$lead->notes()->where('pinned', true)->get();
$lead->notes()->create(['body' => 'x']);
$lead->user()->associate($user)->save();
$lead->load('notes');  $lead->relationLoaded('notes');
```

O método da relação tem de declarar o tipo de retorno (`HasMany`, `HasOne`, `BelongsTo`) — é assim que `$lead->notes`
distingue relações de outros métodos. FK por convenção: `{snake(classe)}_id` (`Lead` → `lead_id`).

**Sem eager loading** (`with()` lança `LogicException`; `$with` é ignorado): numa listagem, evita N+1 fazendo `User::whereIn('id', $leads->pluck('user_id'))->get()->keyBy('id')`.

## Eventos

```php
protected static function booted(): void
{
    static::creating(fn (Lead $l) => $l->source ??= 'landing');
    static::saved(fn (Lead $l) => Log::info('saved', ['id' => $l->id]));
    static::deleting(fn (Lead $l) => $l->status !== 'won');     // false cancela
}
```

## Soft deletes e UUIDs

```php
use Slendie\Model\SoftDeletes;   // coluna deleted_at ($table->softDeletes())
Lead::withTrashed()->get();  Lead::onlyTrashed()->restore();  $lead->trashed();  $lead->forceDelete();

use Slendie\Model\HasUuids;      // chave primária uuid gerada em creating
```

## Route-model binding

```php
Route::get('/leads/{lead}', fn (Lead $lead) => $lead);          // por chave primária (ou getRouteKeyName())
Route::get('/posts/{post:slug}', fn (Post $post) => $post);     // por outro campo
```

Registo inexistente (ou apagado com SoftDeletes) → `ModelNotFoundException` → 404. `route('posts.show', ['post' => $post])`
usa `getRouteKey()`.

## Serialização

```php
$lead->toArray();  $lead->toJson();  (string) $lead;
return $lead;                     // JSON no controller
$lead->makeVisible('ip')->toArray();
```

## Collection

`Lead::all()` devolve `Slendie\Model\Collection`: `map`, `filter`, `pluck`, `keyBy`, `groupBy`, `sortBy`, `first`,
`where`, `contains`, `sum`, `isEmpty`, `toArray`, `toJson`, `each`, `chunk`, `load`, `find`, `modelKeys`, `fresh`, …
(`toJson()` aplica `$hidden`/`$visible` de cada model). Sem macros.

Notas: `pluck()`/`value()` no builder do model devolvem valores em bruto (sem casts), e os closures de
`where(fn ($q) => …)` recebem o Query Builder base.

## Diferenças para o Eloquent
Sem `with()` (eager loading), `belongsToMany`, relações polimórficas, observers em classe, factories, `Attribute::make`,
`preventLazyLoading`, `pruning`. Ver [laravel-differences.md](laravel-differences.md).
