# Base de dados

Drivers: **SQLite** (default), **MySQL/MariaDB**, **PostgreSQL**. Configuração em `config/database.php` e `.env`.

```
DB_CONNECTION=sqlite            # mysql | pgsql
DB_DATABASE=database/database.sqlite
```

## Queries directas

```php
use Slendie\Support\Facades\DB;

$lead  = DB::query('SELECT * FROM leads WHERE email = ?', [$email])->fetch();      // array|null
$leads = DB::query('SELECT * FROM leads WHERE status = ?', ['new'])->fetchAll();   // array de arrays
$n     = DB::query('SELECT COUNT(*) FROM leads')->fetchColumn();

DB::select('SELECT ...', [...]);        // = fetchAll
DB::selectOne('SELECT ...', [...]);     // = fetch
DB::scalar('SELECT COUNT(*) FROM x');
DB::insert('INSERT INTO leads (name) VALUES (?)', ['Ana']);
DB::update('UPDATE leads SET status = ? WHERE id = ?', ['won', 3]);   // linhas afectadas
DB::delete('DELETE FROM leads WHERE id = ?', [3]);
DB::statement('CREATE INDEX ...');
DB::lastInsertId();
```

Com injecção em vez de facade:

```php
public function __construct(private readonly DatabaseManager $db) {}
$this->db->query($sql, $params)->fetch();
```

**Nunca** interpoles valores no SQL. Usa sempre `?` ou `:nome` com o array de parâmetros.

## Transacções

```php
DB::transaction(function () {
    DB::table('leads')->where('id', 1)->update(['status' => 'won']);
    DB::table('events')->insert([...]);
});                                       // rollback automático em excepção

DB::beginTransaction();  DB::commit();  DB::rollBack();
```

Transacções aninhadas usam *savepoints* nos três drivers.

## Várias ligações

```php
'connections' => ['sqlite' => [...], 'reporting' => ['driver' => 'mysql', ...]],

DB::connection('reporting')->select('...');
Lead::on('reporting')->count();
```

## Query log (debug)

```php
DB::enableQueryLog();
// ...
DB::getQueryLog();                        // [['sql' => ..., 'bindings' => [...], 'time' => 1.2], ...]
DB::listen(fn ($query) => Log::debug($query->sql, $query->bindings));
```

## Erros

`QueryException` traz `getSql()` e `getBindings()`. Se a tabela não existir, a mensagem sugere `php slendie migrate`.

## Notas por driver

- **SQLite**: `foreign_key_constraints => true` activa `PRAGMA foreign_keys`. Ficheiro criado automaticamente pelo `migrate`.
- **MySQL**: `strict => true` activa `sql_mode` estrito; `utf8mb4_unicode_ci`.
- **PostgreSQL**: `search_path`; `insertGetId` usa `RETURNING`.
