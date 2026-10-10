# core-04-04 — Database e Query Builder

> Peça 04 · Depende de: 01 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Base de dados), §6 (Drivers)

## Objectivo
Ligação PDO a SQLite/MySQL/PostgreSQL, `$db->query($sql, $params)->fetch()/fetchAll()`, e um Query Builder fluente
com prepared statements e grammars por driver. Sem Model (peça 05) nem Schema (peça 06).

## Ficheiros a criar (`framework/src/Database/`)
- `DatabaseManager.php`
  - `__construct(Application $app, array $config)` (de `config/database.php`: `default`, `connections.{name}`)
  - `connection(?string $name = null): Connection` (lazy, cache por nome), `purge(name)`, `reconnect(name)`, `getDefaultConnection()`, `setDefaultConnection()`
  - `extend(string $driver, Closure $resolver)` — packages adicionam drivers
  - `__call` delega para a connection default: `query()`, `table()`, `select()`, `insert()`, `transaction()`, …
- `Connection.php`
  - `__construct(PDO|Closure $pdo, string $driver, string $database, array $config, Grammar $grammar)`; PDO lazy (closure) para não ligar sem uso
  - `getPdo()`, `getDriverName()`, `getDatabaseName()`, `getGrammar()`
  - `query(string $sql, array $bindings = []): Statement` — prepara e executa; devolve wrapper `Statement`
  - `select(string $sql, array $bindings = []): array` (todas as linhas, `FETCH_ASSOC`), `selectOne(): ?array`, `scalar(): mixed`
  - `insert(sql, bindings): bool`, `update(sql, bindings): int` (rowCount), `delete(sql, bindings): int`, `statement(sql, bindings): bool`, `affectingStatement(): int`, `unprepared(sql): bool`
  - `lastInsertId(?string $sequence = null): string|int`
  - `transaction(Closure $callback, int $attempts = 1): mixed` (com rollback em Throwable), `beginTransaction/commit/rollBack`, `transactionLevel()`; savepoints para transacções aninhadas (`SAVEPOINT trans{n}`), suportadas pelos 3 drivers
  - `table(string $table, ?string $as = null): Query\Builder`
  - `enableQueryLog/disableQueryLog/getQueryLog/flushQueryLog` (lista `[sql, bindings, time_ms]`), `listen(Closure)` (callback por query — útil para debug bar/testes)
  - `pretend(Closure)`: devolve queries sem executar (para `migrate --pretend`)
  - Excepções: envolver `PDOException` em `QueryException` com `getSql()`, `getBindings()`, mensagem com SQL e bindings interpolados **só para a mensagem** (nunca executado), e dica "Tabela 'x' não existe. Correu `php slendie migrate`?" quando a mensagem do driver indicar tabela inexistente (`no such table`, `1146`, `42P01`)
  - Bindings: converte `bool` → int, `DateTimeInterface` → `Y-m-d H:i:s`, `BackedEnum` → value; `null` com `PDO::PARAM_NULL`; int com `PARAM_INT`
- `Statement.php` — wrapper de `PDOStatement`: `fetch(): ?array`, `fetchAll(): array`, `fetchColumn(int $i = 0)`, `fetchObject(string $class = stdClass::class)`, `rowCount()`, `getPdoStatement()`; `fetch()` devolve `null` (não `false`) quando não há linha
- `ConnectionFactory.php` — cria PDO a partir da config por driver:
  - `sqlite`: `database` (path absoluto, relativo a `database_path()`, ou `:memory:`); `foreign_key_constraints: true` → `PRAGMA foreign_keys = ON`; `busy_timeout`; `journal_mode: wal` opcional
  - `mysql`: host, port(3306), database, username, password, charset(utf8mb4), collation(utf8mb4_unicode_ci), `unix_socket`, `options`, `strict: true` → `SET SESSION sql_mode='STRICT_ALL_TABLES,...'`, timezone
  - `pgsql`: host, port(5432), database, username, password, charset(utf8), `search_path`/`schema`, `sslmode`
  - opções PDO comuns: `ERRMODE_EXCEPTION`, `FETCH_ASSOC`, `EMULATE_PREPARES=false`, `STRINGIFY_FETCHES=false`
  - `DatabaseManager::extend` para drivers externos
- `Query/Builder.php`
  - estado: `from`, `columns`, `distinct`, `joins`, `wheres`, `groups`, `havings`, `orders`, `limit`, `offset`, `bindings` por secção (`select`, `join`, `where`, `having`, `order`)
  - **select**: `select(...$cols)` (strings; `'x as y'`; `Expression`), `addSelect()`, `selectRaw(sql, bindings)`, `distinct()`, `from(table, as)`
  - **joins**: `join(table, first, operator, second, type = 'inner')`, `leftJoin`, `rightJoin`, `crossJoin`, `join(table, Closure)` com `on()/orOn()/where()`
  - **where**: `where(column, operator?, value?)` (2 args ⇒ `=`; array assoc ⇒ AND de igualdades; Closure ⇒ grupo com parênteses), `orWhere`, `whereNot`, `whereLike(column, value, caseSensitive = false)` → `LIKE` (`ILIKE` em pgsql quando insensitive; `LOWER()` noutros se `caseSensitive=false` e driver não suportar — **decisão**: para simplicidade, `whereLike` adiciona `%` automático se o valor **não** contiver `%` nem `_`; documentar), `orWhereLike`, `whereNotLike`, `whereIn/whereNotIn/orWhereIn` (array vazio ⇒ `0 = 1` / `1 = 1`), `whereNull/whereNotNull/orWhereNull`, `whereBetween/whereNotBetween`, `whereDate/whereYear/whereMonth/whereDay` (via grammar), `whereColumn(a, op, b)`, `whereRaw(sql, bindings)`, `orWhereRaw`, `whereExists(Closure)`, `when(cond, Closure, ?Closure)`, `unless()`
  - **group/having/order**: `groupBy(...)`, `having(col, op, val)`, `havingRaw`, `orderBy(col, dir = 'asc')` (valida `asc|desc`), `orderByDesc`, `latest(col = 'created_at')`, `oldest()`, `inRandomOrder()`, `orderByRaw`, `reorder()`
  - **limit**: `limit(n)`, `take()`, `offset(n)`, `skip()`, `forPage(page, perPage)`
  - **fetch**: `get(array $cols = ['*']): array` (linhas assoc), **`fetchAll()`** = `get()`, `first(): ?array`, **`fetch()`** = `first()`, `firstOrFail()` (lança `RecordNotFoundException`), `find(id, cols)` (por `id`), `value(col)`, `pluck(col, ?key): array`, `count(col = '*'): int`, `sum/avg/min/max`, `exists(): bool`, `doesntExist()`, `chunk(size, Closure)`, `cursor(): Generator`, `paginate(perPage = 15, page = null, pageName = 'page'): Paginator`, `simplePaginate()`
  - **write**: `insert(array $values): bool` (uma linha ou várias, mesma ordem de colunas), `insertGetId(array, ?string $sequence)` (pgsql: `RETURNING id`), `insertOrIgnore()`, `update(array): int`, `updateOrInsert(attrs, values)`, `increment/decrement(col, amount = 1, extra = [])`, `delete(?id): int`, `truncate()`
  - **sql**: `toSql(): string`, `getBindings(): array`, `toRawSql()` (interpolado, só para debug), `dd()`, `dump()`
  - `newQuery()`, `clone`, `__clone` profundo
  - Validação de identificadores: nomes de tabela/coluna (excepto `Expression`) têm de corresponder a `^[A-Za-z_][A-Za-z0-9_]*(\.[A-Za-z_][A-Za-z0-9_]*)?( as [A-Za-z_][A-Za-z0-9_]*)?$` (case-insensitive no `as`); caso contrário `InvalidArgumentException`. Operadores aceites: lista fechada (`= < > <= >= <> != like not like ilike in not in is is not between`)
- `Query/Expression.php` — valor cru (`DB::raw('COUNT(*)')`)
- `Query/JoinClause.php extends Builder`
- `Query/Grammar.php` (abstract) — `compileSelect(Builder)`, `compileInsert`, `compileInsertGetId`, `compileInsertOrIgnore`, `compileUpdate`, `compileDelete`, `compileTruncate`, `compileExists`, `wrap(identifier)` (quoting com `"` default; `.` separa tabela.coluna; `*` não é citado; `as` alias), `wrapTable`, `parameter()`, `columnize()`, `compileWheres` (dispatch por tipo), `compileLimit/Offset`, `compileLock`(não), `compileRandom()`, `getDateBasedWhere(type)`
- `Query/Grammars/SqliteGrammar.php` — `wrap` com `"`; `compileInsertOrIgnore` → `INSERT OR IGNORE`; `compileTruncate` → `DELETE FROM x; DELETE FROM sqlite_sequence WHERE name = ?`; date wheres via `strftime('%Y', col)`; `RANDOM()`; LIKE é case-insensitive para ASCII por defeito
- `Query/Grammars/MySqlGrammar.php` — `wrap` com backticks; `INSERT IGNORE`; `RAND()`; `YEAR(col)`; `LIKE` com collation (insensitive por defeito)
- `Query/Grammars/PgsqlGrammar.php` — `wrap` com `"`; `INSERT ... ON CONFLICT DO NOTHING`; `RANDOM()`; `EXTRACT(YEAR FROM col)`; `ILIKE` para `whereLike` insensitive; `insertGetId` com `RETURNING`; `TRUNCATE ... RESTART IDENTITY CASCADE`
- `Pagination/Paginator.php` — `items()`, `total()`, `perPage()`, `currentPage()`, `lastPage()`, `hasMorePages()`, `nextPageUrl()`, `previousPageUrl()`, `url(page)`, `withPath()`, `appends(array)`, `links(string $view = 'pagination::default'): string` (view Blade simples em `framework/resources/views/pagination/{default,simple}.blade.php` — como o Blade só chega na peça 03 e esta é independente, registar namespace `pagination` no `DatabaseServiceProvider::boot` **só se** `Factory` existir no container), `toArray()` (`data`, `total`, `per_page`, `current_page`, `last_page`, `from`, `to`), `JsonSerializable`, `IteratorAggregate`, `Countable`, `ArrayAccess`; `SimplePaginator` (sem total)
- `DatabaseServiceProvider.php` — singleton `DatabaseManager` (`'db'`), `Connection` default resolve via manager; facade `Support\Facades\DB` (`DB::table()`, `DB::query()`, `DB::select()`, `DB::transaction()`, `DB::raw()`, `DB::connection('x')`)
- `QueryException`, `RecordNotFoundException`
- `skeleton/config/database.php`:
  ```php
  return [
      'default' => env('DB_CONNECTION', 'sqlite'),
      'connections' => [
          'sqlite' => ['driver' => 'sqlite', 'database' => env('DB_DATABASE', database_path('database.sqlite')), 'foreign_key_constraints' => true],
          'mysql'  => ['driver' => 'mysql', 'host' => env('DB_HOST', '127.0.0.1'), 'port' => env('DB_PORT', '3306'), 'database' => env('DB_DATABASE', 'slendie'), 'username' => env('DB_USERNAME', 'root'), 'password' => env('DB_PASSWORD', ''), 'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true],
          'pgsql'  => ['driver' => 'pgsql', 'host' => env('DB_HOST', '127.0.0.1'), 'port' => env('DB_PORT', '5432'), 'database' => env('DB_DATABASE', 'slendie'), 'username' => env('DB_USERNAME', 'postgres'), 'password' => env('DB_PASSWORD', ''), 'charset' => 'utf8', 'search_path' => 'public'],
      ],
      'migrations' => 'migrations',
  ];
  ```

## Testes (Pest)
- Unit por grammar (sem BD): `toSql()` + bindings para: select simples, colunas com alias, distinct, joins (inner/left/closure), todos os tipos de where (incl. closure aninhada, `whereIn` vazio, `whereLike` auto-%), group/having, order (e validação de direcção inválida), limit/offset, insert (1 e N linhas), insertOrIgnore, update, delete, truncate, exists, count/sum. Um ficheiro de teste por grammar com strings esperadas exactas (SQLite `"x"`, MySQL `` `x` ``, Pgsql `"x"` + `ILIKE`).
- Unit: identificador inválido lança; operador inválido lança.
- Feature com SQLite `:memory:` (tabela criada por `statement()` cru): CRUD completo via builder; `fetch()/fetchAll()` sinónimos; `first()` → `null` quando vazio; `firstOrFail` lança; `paginate` (total, lastPage, items); `chunk`; `transaction` com rollback em excepção; savepoint aninhado; `insertGetId`; `increment`; `pluck` com chave; `QueryException` traz SQL e dica de migração para tabela inexistente; query log regista tempo.
- Feature MySQL e PostgreSQL: **mesmo conjunto** executado condicionalmente se `DB_TEST_MYSQL_DSN`/`DB_TEST_PGSQL_DSN` estiverem no ambiente (CI tem serviços para ambos); localmente são skipped com mensagem.

## Critérios de aceitação
- `DB::query('SELECT * FROM leads WHERE email = ?', [$e])->fetch()` e `->fetchAll()` funcionam como no brief.
- `DB::table('leads')->whereLike('name', 'silva')->orderBy('id', 'desc')->paginate(20)` gera SQL válido nos 3 drivers.
- Nenhuma string de utilizador alguma vez concatenada no SQL (só identificadores validados por regex e `Expression`).
- `composer test/analyse/lint` verdes; `docs/database.md` e `docs/query-builder.md` escritos.

## Não fazer nesta peça
Model (05), Schema/migrações (06), comandos (07), eager loading, locks, unions, subqueries em select (só `whereExists`).

## Desvios
(preencher na implementação)
