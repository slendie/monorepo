# Migrações e Schema

```bash
php slendie make:migration create_leads_table          # infere tabela e stub create
php slendie make:migration add_phone_to_leads_table    # stub update
php slendie migrate
php slendie migrate:status
php slendie migrate:rollback --step=1
php slendie migrate:fresh --seed
```

```php
<?php

declare(strict_types=1);

use Slendie\Database\Migrations\Migration;
use Slendie\Database\Schema\Blueprint;
use Slendie\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 120);
            $table->string('email')->index();
            $table->string('phone', 30)->nullable();
            $table->text('message')->nullable();
            $table->string('status', 20)->default('new')->index();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
```

Migrações correm dentro de uma transacção (excepto em MySQL, cujo DDL não é transaccional) e são registadas na
tabela `migrations` com número de *batch*. `rollback` chama `down()` do último batch. Se `up()` falhar a meio,
a transacção é desfeita (SQLite e PostgreSQL) e a migração não fica registada.

Os ficheiros chamam-se `{YYYY_MM_DD_HHMMSS}_{nome_em_snake}.php`, correm por ordem de nome e podem devolver uma classe
anónima (recomendado) ou declarar uma classe nomeada (`CreateLeadsTable`, deduzida do nome do ficheiro).

## A classe Migration

```php
return new class extends Migration
{
    protected ?string $connection = 'mysql';   // ligação onde corre (por omissão a do Migrator)
    public bool $withinTransaction = false;    // sem transacção (ignorado em MySQL, que nunca a usa)

    public function shouldRun(): bool { return true; }   // false = salta (e não regista)

    public function up(): void   { $this->schema()->create(...); }   // ou a facade Schema::
    public function down(): void { $this->db()->statement('...'); }
};
```

`$this->schema()` e `$this->db()` seguem sempre `$connection`. Durante a migração a ligação por omissão (`DB::`, `Schema::`)
passa a ser a dela. Fora do `Migrator`, `schema()`/`db()` lançam `LogicException`.

`Schema::connection('mysql')->hasTable('leads')` devolve o `Schema\Builder` de outra ligação. Também existem
`getTables()`, `getColumns($table)` (nome, tipo, nullable, default), `hasColumns()`, `dropAllTables()`,
`enableForeignKeyConstraints()`/`disableForeignKeyConstraints()`.

## Alterar tabelas

```php
Schema::table('leads', function (Blueprint $table): void {
    $table->string('source', 50)->nullable()->after('status');   // after: só MySQL
    $table->dropColumn(['ip', 'user_agent']);
    $table->renameColumn('msg', 'message');
    $table->index(['status', 'created_at']);
    $table->dropIndex(['email']);
    $table->string('name', 200)->change();                       // não em SQLite
});

Schema::rename('leads', 'contacts');
Schema::drop('x');  Schema::dropIfExists('x');
Schema::hasTable('leads');  Schema::hasColumn('leads', 'email');  Schema::getColumnListing('leads');
Schema::withoutForeignKeyConstraints(fn () => ...);
```

## Tipos de coluna → SQL por driver

Tudo o que é gerado passa por identificadores validados (`[A-Za-z_][A-Za-z0-9_]*`); defaults, comentários e valores de
enum são sempre literais citados.

| Blueprint | SQLite | MySQL / MariaDB | PostgreSQL |
|---|---|---|---|
| `id()` / `bigIncrements()` | `integer primary key autoincrement` | `bigint unsigned auto_increment primary key` | `bigserial primary key` |
| `increments()` / `smallIncrements()` | `integer primary key autoincrement` | `int` / `smallint unsigned auto_increment primary key` | `serial` / `smallserial primary key` |
| `uuid()` | `text` | `char(36)` | `uuid` |
| `string(n = 255)` | `varchar(n)` | `varchar(n)` | `varchar(n)` |
| `char(n)` | `varchar(n)` | `char(n)` | `char(n)` |
| `text()` | `text` | `text` | `text` |
| `mediumText()` / `longText()` | `text` | `mediumtext` / `longtext` | `text` |
| `integer()` | `integer` | `int` | `integer` |
| `bigInteger()` | `integer` | `bigint` | `bigint` |
| `smallInteger()` | `integer` | `smallint` | `smallint` |
| `tinyInteger()` | `integer` | `tinyint` | `smallint` |
| `unsigned*()` / `->unsigned()` | `integer` (ignorado) | `… unsigned` | (ignorado) |
| `integer($col, autoIncrement: true)` | `integer primary key autoincrement` | `int auto_increment primary key` | `serial primary key` |
| `float(col, precision = 53)` | `real` | `float` (≤ 24) / `double` | `real` (≤ 24) / `double precision` |
| `double()` | `real` | `double` | `double precision` |
| `decimal(col, total = 8, places = 2)` | `numeric` | `decimal(t, p)` | `decimal(t, p)` |
| `boolean()` | `tinyint(1)` | `tinyint(1)` | `boolean` |
| `date()` | `date` | `date` | `date` |
| `dateTime(col, p = 0)` / `timestamp(col, p = 0)` | `datetime` | `datetime(p)` / `timestamp(p)` | `timestamp(p) without time zone` |
| `dateTimeTz()` / `timestampTz()` | `datetime` | `datetime(p)` / `timestamp(p)` | `timestamp(p) with time zone` |
| `time(col, p = 0)` | `time` | `time(p)` | `time(p) without time zone` |
| `year()` | `integer` | `year` | `integer` |
| `json()` | `text` | `json` | `json` |
| `jsonb()` | `text` | `json` | `jsonb` |
| `binary()` | `blob` | `blob` | `bytea` |
| `enum(col, [...])` | `text check (col in (...))` | `enum(...)` | `varchar(255) check (col in (...))` |
| `ipAddress()` / `macAddress()` | `varchar(45)` / `varchar(17)` | `varchar(45)` / `varchar(17)` | `inet` / `macaddr` |
| `foreignId()` | `integer` | `bigint unsigned` | `bigint` |
| `foreignUuid()` | `text` | `char(36)` | `uuid` |

Atalhos: `timestamps(p = 0)` / `timestampsTz()` (`created_at` e `updated_at`, nullable), `softDeletes(col = 'deleted_at')`
(timestamp nullable), `rememberToken()` (`varchar(100)` nullable). Colunas **não** nullable por omissão (`->nullable()`).

Não há `morphs()`, índices full-text/espaciais, colunas geradas (`storedAs`/`virtualAs`) nem partições.

## Modificadores

| Modificador | Efeito |
|---|---|
| `nullable(bool = true)` | `null` / `not null` |
| `default($v)` | bool → `1/0` (SQLite, MySQL) ou `true/false` (PostgreSQL); string citada (`'` escapado); `DB::raw('CURRENT_TIMESTAMP')` fica cru |
| `useCurrent()` | `default current_timestamp` (com `(p)` em MySQL se a precisão for > 0) |
| `useCurrentOnUpdate()` | `on update current_timestamp` — só MySQL |
| `unsigned()` / `autoIncrement()` / `primary()` | ver tabela acima |
| `unique(?name)` / `index(?name)` | cria o índice depois da tabela; nome por omissão `{tabela}_{colunas}_{unique\|index}` (encurtado com hash acima de 60 caracteres) |
| `comment($text)` | `comment '…'` (MySQL) ou `comment on column` (PostgreSQL); ignorado em SQLite |
| `after($col)` / `first()` | só MySQL |
| `charset($c)` / `collation($c)` | só MySQL |
| `change()` | altera uma coluna existente: `modify` (MySQL) ou `alter column … type` (PostgreSQL); **lança excepção em SQLite** |

`change()` redefine a coluna por inteiro: repete `nullable()`, `default()`… senão são removidos.

Tabela: `engine()`, `charset()`, `collation()` (MySQL; por omissão a ligação ou `InnoDB`/`utf8mb4`/`utf8mb4_unicode_ci`),
`temporary()`, `comment()` (MySQL e PostgreSQL).

## Chaves estrangeiras

```php
$table->foreignId('user_id')->constrained();                       // → users.id (deduzido de user_id)
$table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
$table->foreignUuid('team_id')->constrained('teams', 'uuid')->cascadeOnDelete();
$table->foreign('x')->references('id')->on('xs')->onDelete('restrict')->onUpdate('cascade');
```

Acções: `cascade`, `restrict`, `set null`, `no action`, `set default` (`cascadeOnDelete()`, `cascadeOnUpdate()`,
`restrictOnDelete()`, `restrictOnUpdate()`, `nullOnDelete()`, `noActionOnDelete()`). Em SQLite as chaves vão dentro do
`create table`; em MySQL e PostgreSQL são `alter table … add constraint`.

`Schema::withoutForeignKeyConstraints()` usa `PRAGMA foreign_keys` (SQLite), `foreign_key_checks` (MySQL) e
`SET CONSTRAINTS ALL DEFERRED` (PostgreSQL — só afecta restrições `DEFERRABLE` e dentro de uma transacção).

## Limitações do SQLite
- `change()` lança `LogicException` (o SQLite não altera colunas): cria uma tabela nova, copia os dados e troca.
- `dropForeign()`, `dropPrimary()` e adicionar `primary()`/`foreign()` a uma tabela existente lançam `LogicException`:
  estas chaves só podem ser declaradas no `Schema::create()`.
- `dropColumn()` falha se a coluna tiver índice, `unique`, chave primária ou estrangeira — apaga primeiro o índice.
- `after()` e `first()` são ignorados; `comment()` também.
- Adicionar uma coluna `not null` a uma tabela com linhas exige um `default()`.
- `dropColumn`/`renameColumn` requerem SQLite ≥ 3.35 (o PHP 8.5 traz ≥ 3.45).
- `PRAGMA foreign_keys` não tem efeito dentro de uma transacção; `Schema::dropAllTables()` e
  `withoutForeignKeyConstraints()` devem correr fora de uma (o `migrate:fresh` já o faz).
- `enum()` é texto com `check`, `json()`/`uuid()` são texto e `boolean()` é `tinyint(1)`.
- Os inteiros são todos `integer` (só assim o SQLite aceita `autoincrement`).

## SQL cru numa migração

```php
public function up(): void
{
    $this->db()->statement('CREATE VIEW active_leads AS SELECT * FROM leads WHERE status <> ?', ['lost']);
}
```

## Migrações condicionais por driver

```php
public function shouldRun(): bool { return $this->db()->getDriverName() === 'pgsql'; }
```

## Migrações de packages

Packages registam pastas com `loadMigrationsFrom()` (disponível com a peça 09); correm junto com as do projecto. Para copiar para o projecto:
`php slendie vendor:publish --tag=auth-migrations`.

## O Migrator (para quem escreve comandos ou testes)

Os comandos `migrate*` são camadas finas sobre `Slendie\Database\Migrations\Migrator` (singleton no container):

```php
$migrator = app(Migrator::class);
$migrator->path(__DIR__.'/../database/migrations');          // pastas extra (packages)
$migrator->setOutput(fn (string $line) => print $line.PHP_EOL);   // "Migrating: x", "Migrated:  x (12.3ms)"

$migrator->run();                          // list<string> com os nomes corridos (um batch)
$migrator->run(['step' => true]);          // um batch por ficheiro
$migrator->run(['pretend' => true]);       // list<string> com o SQL, sem executar nem registar
$migrator->rollback();                     // último batch; ['step' => 2] ou ['batch' => 3]; 'pretend' também
$migrator->reset();                        // reverte tudo
$migrator->fresh();                        // dropAllTables() + run(), sem precisar dos down()
$migrator->status();                       // [['migration' => ..., 'ran' => bool, 'batch' => ?int], ...]
$migrator->setConnection('mysql');         // migrações e tabela `migrations` noutra ligação
```

O mesmo nome de ficheiro em duas pastas lança `RuntimeException`. Um registo cujo ficheiro desapareceu é ignorado
(com aviso) no `rollback`/`reset`. A tabela de registo vem de `config('database.migrations')` (`migrations`).

`MigrationCreator::create($name, $path, $table = null, $create = false)` gera o ficheiro a partir dos stubs
`migration.create|update|blank.stub` e infere a tabela do nome (`create_leads_table` → `leads`, create;
`add_phone_to_leads_table` → `leads`, update). Recusa nomes duplicados e nomes/tabelas que não sejam identificadores.
