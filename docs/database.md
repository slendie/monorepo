# Base de dados

Drivers: **SQLite** (default), **MySQL/MariaDB**, **PostgreSQL**. Configuração em `config/database.php` e `.env`.

```
DB_CONNECTION=sqlite            # mysql | pgsql
DB_DATABASE=database.sqlite     # SQLite: caminho absoluto, relativo a database/ ou :memory:
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
DB::getQueryLog();                        // [['sql' => ..., 'bindings' => [...], 'time_ms' => 1.2], ...]
DB::flushQueryLog();  DB::disableQueryLog();
DB::listen(fn (string $sql, array $bindings, float $ms) => logger()->debug($sql));

// Ver as queries sem as executar (útil para `migrate --pretend`):
$queries = DB::pretend(fn () => DB::table('leads')->where('id', 1)->delete());
```

## Erros

`QueryException` traz `getSql()` e `getBindings()`. Se a tabela não existir, a mensagem sugere `php slendie migrate`.

## Notas por driver

- **SQLite**: `foreign_key_constraints => true` activa `PRAGMA foreign_keys`; `busy_timeout` e `journal_mode` (`wal`) são opcionais. O ficheiro é criado na primeira ligação (a pasta tem de existir); `:memory:` serve para testes.
- **MySQL**: `strict => true` activa `sql_mode` estrito; `utf8mb4_unicode_ci`.
- **PostgreSQL**: `search_path`; `insertGetId` usa `RETURNING`.
- A ligação é preguiçosa: o PDO só é criado na primeira query.
- Drivers de packages: `DB::extend('driver', fn (array $config, string $name) => new Connection(...))`.

## Correr os testes em MySQL e PostgreSQL

Por omissão os testes de integração correm em SQLite `:memory:`. Para os correr também em MySQL/PostgreSQL define as variáveis abaixo — **apontam para uma base de dados descartável**: os testes fazem `DROP TABLE leads, notes`.

```
DB_TEST_MYSQL_DSN="mysql:host=127.0.0.1;dbname=slendie_test;charset=utf8mb4"
DB_TEST_MYSQL_USER=root
DB_TEST_MYSQL_PASSWORD=
DB_TEST_PGSQL_DSN="pgsql:host=127.0.0.1;dbname=slendie_test"
DB_TEST_PGSQL_USER=postgres
DB_TEST_PGSQL_PASSWORD=
```

Sem as variáveis, esses testes ficam *skipped* com uma mensagem.
