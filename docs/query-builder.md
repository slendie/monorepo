# Query Builder

```php
use Slendie\Support\Facades\DB;

DB::table('leads')->where('status', 'new')->orderBy('created_at', 'desc')->limit(10)->get();
```

Todos os valores vão por *prepared statements*. Nomes de tabelas/colunas são validados (`[A-Za-z_][A-Za-z0-9_.]*`).

## Obter resultados

```php
->get()            ->fetchAll()       // array de linhas (assoc)
->first()          ->fetch()          // linha ou null
->firstOrFail()                       // RecordNotFoundException → 404
->find(5)                             // por id
->value('email')                      // um valor da primeira linha
->pluck('email')   ->pluck('email', 'id')
->count()  ->sum('x')  ->avg('x')  ->min('x')  ->max('x')
->exists()  ->doesntExist()
->paginate(15)     ->simplePaginate(15)
->chunk(100, fn ($rows) => ...)
->cursor()                            // Generator, linha a linha
->toSql()  ->getBindings()  ->dd()
```

`fetch()`/`fetchAll()` e `first()`/`get()` são sinónimos: usa os que te forem naturais.

## Select

```php
->select('id', 'name')  ->select('name as n')  ->addSelect('email')
->selectRaw('COUNT(*) as total, status')  ->distinct()
```

## Where

```php
->where('status', 'new')                   // =
->where('value', '>', 100)
->where(['status' => 'new', 'source' => 'landing'])
->where(fn ($q) => $q->where('a', 1)->orWhere('b', 2))     // parênteses
->orWhere('x', 1)  ->whereNot('x', 1)
->whereLike('name', 'silva')               // LIKE '%silva%' (case-insensitive; % automático se não houver wildcards)
->whereLike('name', 'sil%')                // usa o teu padrão
->orWhereLike(...)  ->whereNotLike(...)
->whereIn('id', [1, 2])  ->whereNotIn(...)
->whereNull('phone')  ->whereNotNull('phone')
->whereBetween('value', [10, 20])
->whereDate('created_at', '2026-10-10')  ->whereYear(...)  ->whereMonth(...)  ->whereDay(...)
->whereColumn('updated_at', '>', 'created_at')
->whereRaw('LOWER(email) = ?', [$e])
->whereExists(fn ($q) => $q->from('events')->whereColumn('events.lead_id', 'leads.id'))
->when($request->filled('q'), fn ($q, $v) => $q->whereLike('name', $request->q))
->unless($admin, fn ($q) => $q->where('public', true))
```

## Joins, agrupar, ordenar, limitar

```php
->join('users', 'users.id', '=', 'leads.user_id')
->leftJoin('users', fn ($j) => $j->on('users.id', '=', 'leads.user_id')->where('users.active', 1))
->groupBy('status')->having('total', '>', 5)->havingRaw('COUNT(*) > 1')
->orderBy('name')  ->orderByDesc('id')  ->latest()  ->oldest()  ->inRandomOrder()  ->reorder()
->limit(10)->offset(20)  ->take(10)->skip(20)  ->forPage(3, 20)
```

## Escrever

```php
->insert(['name' => 'Ana'])  ->insert([[...], [...]])
->insertGetId([...])
->insertOrIgnore([...])
->where('id', 1)->update(['status' => 'won'])
->updateOrInsert(['email' => $e], ['name' => 'Ana'])
->increment('visits')  ->decrement('stock', 2)
->where('id', 1)->delete()  ->delete(1)
->truncate()
```

## Expressões cruas

```php
DB::raw('COUNT(*)');
->select(DB::raw('COUNT(*) as total'));
->orderByRaw('RANDOM()');
```

## Paginação

```php
$leads = DB::table('leads')->paginate(20);           // lê ?page=
$leads->items();  $leads->total();  $leads->lastPage();  $leads->hasMorePages();
$leads->nextPageUrl();  $leads->appends(['q' => 'x']);
```

```blade
@foreach ($leads as $lead) … @endforeach
{!! $leads->links() !!}
```

Em JSON (`return $leads;`): `{data, total, per_page, current_page, last_page, from, to}`.

## Fora de âmbito
`union`, subqueries em `select`/`from`, `lockForUpdate`, `upsert`, full-text, JSON where (`->`).
