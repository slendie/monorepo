# core-04-06 — Schema, Migrações e Seeders

> Peça 06 · Depende de: 04 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (Migrações), análise crítica §3.6

## Objectivo
Migrações como classes com `up()/down()` reais, Schema builder com `Blueprint` que gera DDL para SQLite, MySQL e
PostgreSQL, repositório de migrações com batches, rollback que reverte schema, e seeders. Os comandos de consola
ficam para a peça 07, mas o `Migrator` já expõe tudo o que eles precisam.

## Ficheiros a criar (`framework/src/Database/`)
### Schema
- `Schema/Builder.php` — `__construct(Connection)`; `create(table, Closure)`, `table(table, Closure)` (alter), `drop(table)`, `dropIfExists(table)`, `rename(from, to)`, `hasTable(table): bool`, `hasColumn(table, col)`, `hasColumns()`, `getColumnListing(table): array`, `getColumns(table): array` (nome, tipo, nullable, default), `getTables(): array`, `dropAllTables()`, `disableForeignKeyConstraints()/enableForeignKeyConstraints()/withoutForeignKeyConstraints(Closure)`, `getConnection()`; `blueprintResolver`
- `Schema/Blueprint.php` — acumula `columns` e `commands`:
  - colunas: `id(name = 'id')` (bigIncrements), `increments/bigIncrements/smallIncrements`, `uuid(name = 'uuid')`, `string(name, length = 255)`, `char`, `text`, `mediumText`, `longText`, `integer/bigInteger/smallInteger/tinyInteger/unsignedInteger/unsignedBigInteger/unsignedTinyInteger`, `float(name, precision = 53)`, `double`, `decimal(name, total = 8, places = 2)`, `boolean`, `date`, `dateTime(name, precision = 0)`, `timestamp`, `time`, `year`, `json`, `jsonb` (pgsql; json noutros), `binary`, `enum(name, array $allowed)` (MySQL ENUM; CHECK noutros), `ipAddress`, `macAddress`, `foreignId(name)` (unsignedBigInteger) com `->constrained(?table, column = 'id')`, `foreignUuid`, `timestamps(precision = 0)` (created_at/updated_at nullable), `timestampsTz`, `softDeletes(col = 'deleted_at')`, `rememberToken()`, `morphs` **não**
  - comandos: `primary(cols, ?name)`, `unique(cols, ?name)`, `index(cols, ?name)`, `fullText` **não**, `foreign(col)->references(col)->on(table)->onDelete(action)->onUpdate(action)->cascadeOnDelete()->nullOnDelete()->restrictOnDelete()`, `dropColumn(string|array)`, `renameColumn(from, to)`, `dropPrimary/dropUnique/dropIndex/dropForeign(name|array cols)`, `dropTimestamps()`, `dropSoftDeletes()`, `comment(text)` (table; MySQL/pgsql), `engine`, `charset`, `collation` (MySQL), `temporary()`
  - `Schema/ColumnDefinition.php` (fluente, devolvido por cada método de coluna): `nullable(bool = true)`, `default(mixed)` (Expression permitido: `DB::raw('CURRENT_TIMESTAMP')`), `useCurrent()`, `useCurrentOnUpdate()` (MySQL), `unsigned()`, `autoIncrement()`, `primary()`, `unique(?name)`, `index(?name)`, `comment()`, `after(col)` (MySQL), `first()`, `change()` (alter), `charset/collation`, `storedAs/virtualAs` **não**
  - `Schema/ForeignKeyDefinition.php`, `Schema/IndexDefinition.php`
  - `Schema/ColumnType.php` enum
- `Schema/Grammars/Grammar.php` (abstract) — `compileCreate(Blueprint, Command, Connection): array|string`, `compileAdd`, `compileChange`, `compileDrop`, `compileDropIfExists`, `compileRename`, `compileRenameColumn`, `compileDropColumn`, `compilePrimary/Unique/Index/Foreign`, `compileDropPrimary/...`, `compileTableExists`, `compileColumnListing`, `compileGetTables`, `compileEnableForeignKeyConstraints/Disable`, `getColumns(Blueprint)`, `getType(ColumnDefinition)` (dispatch `type{Studly}`), `addModifiers` (ordem: unsigned, charset, collate, nullable, default, increment, comment, after, first), `getDefaultValue(mixed)` (bool→1/0 ou true/false conforme driver; string citada com `'` escapando `'`; Expression cru)
- `Schema/Grammars/SqliteGrammar.php` — tipos: `INTEGER PRIMARY KEY AUTOINCREMENT` para increments; `VARCHAR`, `TEXT`, `INTEGER`, `REAL`, `NUMERIC`, `TINYINT(1)` para bool, `DATETIME`, `DATE`, `TEXT` para json/uuid/enum (+ `CHECK (col IN (...))` para enum); FK inline na criação (`FOREIGN KEY (...) REFERENCES ...`); **alter limitado**: `ADD COLUMN` ok; `dropColumn` e `renameColumn` via `ALTER TABLE ... DROP/RENAME COLUMN` (SQLite ≥ 3.35, PHP 8.5 traz ≥ 3.45 — assumir disponível); `change()` → lança `LogicException('SQLite não suporta alterar colunas; recrie a tabela')`; `dropForeign` → `LogicException`; índices em comandos separados; `PRAGMA foreign_keys = OFF/ON`; `compileTableExists` via `sqlite_master`; `compileColumnListing` via `PRAGMA table_info`
- `Schema/Grammars/MySqlGrammar.php` — `BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY`, `VARCHAR(n)`, `TEXT/MEDIUMTEXT/LONGTEXT`, `INT`, `TINYINT(1)`, `DOUBLE`, `DECIMAL(t,p)`, `DATETIME(p)`, `TIMESTAMP`, `JSON`, `CHAR(36)` para uuid, `ENUM(...)`, `BLOB`; modifiers `UNSIGNED`, `CHARACTER SET`, `COLLATE`, `COMMENT`, `AFTER`, `FIRST`, `ON UPDATE CURRENT_TIMESTAMP`; `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`; `SET FOREIGN_KEY_CHECKS=0/1`; `information_schema` para listagens; `MODIFY` para `change()`
- `Schema/Grammars/PgsqlGrammar.php` — `BIGSERIAL PRIMARY KEY` (ou `GENERATED BY DEFAULT AS IDENTITY`), `VARCHAR(n)`, `TEXT`, `INTEGER/BIGINT/SMALLINT`, `BOOLEAN`, `DOUBLE PRECISION`, `DECIMAL`, `TIMESTAMP(p) WITHOUT TIME ZONE`, `TIMESTAMPTZ`, `JSON/JSONB`, `UUID`, `VARCHAR(255) CHECK (col IN (...))` para enum, `BYTEA`, `INET`, `MACADDR`; sem unsigned (ignorar); `COMMENT ON COLUMN` separado; `ALTER COLUMN ... TYPE` para change; `SET CONSTRAINTS ALL DEFERRED/IMMEDIATE` para FK toggle (ou `session_replication_role`); `information_schema`
- `Schema/SchemaServiceProvider.php`? **Não**: registar `Schema\Builder` no `DatabaseServiceProvider` (`'db.schema'`) resolvendo com a connection default; facade `Support\Facades\Schema` com `Schema::connection('x')`

### Migrações
- `Migrations/Migration.php` (abstract) — `?string $connection = null`, `bool $withinTransaction = true` (MySQL não suporta DDL transaccional → ignorar flag em MySQL e avisar em debug), `up(): void`, `down(): void`; acesso a `$this->schema(): Schema\Builder` e `$this->db(): Connection`; `shouldRun(): bool` (default true; permite migrações condicionais por driver)
- `Migrations/MigrationRepository.php` — tabela `migrations` (`id` increments, `migration` string unique, `batch` integer); `createRepository()`, `repositoryExists()`, `getRan(): array`, `getMigrations(steps)`, `getMigrationsByBatch(batch)`, `getLast(): array`, `log(name, batch)`, `delete(name)`, `getNextBatchNumber()`, `getLastBatchNumber()`, `deleteRepository()`
- `Migrations/Migrator.php`
  - `__construct(MigrationRepository, DatabaseManager, array $paths = [])`; `path(string)` / `paths()` (framework + app + packages via `loadMigrationsFrom`)
  - `run(array $options = ['pretend' => false, 'step' => false]): array` (nomes corridos) — descobre ficheiros `*.php` em todos os paths, ordena por nome (prefixo `YYYY_MM_DD_HHMMSS_`), filtra os já corridos, corre cada um em transacção (se driver suportar e `withinTransaction`), regista no batch (um batch por `run`, ou um por ficheiro com `step`)
  - `rollback(array $options = ['step' => 1, 'batch' => null, 'pretend' => false]): array` — últimos `step` batches (ou batch específico) por ordem inversa, chama `down()`, apaga registo
  - `reset()` — rollback de tudo; `fresh()` — `dropAllTables()` + `run()` (sem precisar de `down()`)
  - `status(): array` de `['migration' => name, 'ran' => bool, 'batch' => ?int]`
  - `resolve(string $file): Migration` — `require` devolve instância (anonymous class `return new class extends Migration {...}`) **ou** nome de classe (compatível com classes nomeadas: `CreateLeadsTable`); ambos aceites
  - `pretend` → usa `Connection::pretend` e devolve lista de SQL
  - eventos simples: `setOutput(Closure $writer)` para o comando imprimir `Migrating: x`, `Migrated: x (12.3ms)`
  - ficheiros com o mesmo nome em paths diferentes → `RuntimeException` clara
- `Migrations/MigrationCreator.php` — `create(string $name, string $path, ?string $table = null, bool $create = false): string` — gera `{Y_m_d_His}_{snake(name)}.php` a partir de stubs (`migration.create.stub`, `migration.update.stub`, `migration.blank.stub`); infere tabela do nome (`create_leads_table` → `leads`, create; `add_phone_to_leads_table` → `leads`, update); recusa nome duplicado
- `Seeders/Seeder.php` (abstract) — `run(): void`, `call(string|array $classes)`, `callWith`, `__construct(Container)`; `setContainer`
- `DatabaseServiceProvider` — registar `MigrationRepository` (tabela de `config('database.migrations', 'migrations')`), `Migrator` (singleton; paths iniciais `database_path('migrations')`), `MigrationCreator`, `Schema\Builder`

### Stubs (`framework/stubs/`)
- `migration.create.stub`:
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
          Schema::create('{{ table }}', function (Blueprint $table): void {
              $table->id();
              $table->timestamps();
          });
      }

      public function down(): void
      {
          Schema::dropIfExists('{{ table }}');
      }
  };
  ```
- `migration.update.stub` (`Schema::table` com `//` em up/down), `migration.blank.stub`, `seeder.stub`

### Skeleton
- `skeleton/database/migrations/2026_01_01_000000_create_leads_table.php` — `id, name string, email string index, phone string nullable, message text nullable, source string(50) nullable, status string(20) default 'new' index, ip string(45) nullable, user_agent string nullable, timestamps`
- `skeleton/database/seeders/DatabaseSeeder.php` com `LeadSeeder` exemplo (3 leads)

## Testes (Pest)
- Unit por grammar (sem BD): DDL esperado exacto para `create` com todos os tipos de coluna e modifiers; FK com acções; índices; `drop/dropIfExists/rename`; `table()` add/dropColumn/renameColumn; `change()` em MySQL/pgsql e `LogicException` em SQLite; `enum` como ENUM (MySQL) e CHECK (outros); defaults bool/string/Expression; `timestamps()` nullable.
- Feature SQLite `:memory:`: `create` + `hasTable/hasColumn/getColumnListing`; `table()` add column; `drop`; FK enforcement com `PRAGMA foreign_keys` (delete em cascata funciona); `withoutForeignKeyConstraints`.
- Feature MySQL/pgsql condicionais (como na peça 04): mesma bateria básica + `change()`.
- Migrator (SQLite, pasta temporária de migrações criada no teste): `run` cria repositório e corre por ordem; segunda `run` não repete; `rollback` chama `down` e remove registo; `step`; `reset`; `fresh`; `status`; classe anónima e classe nomeada; nome duplicado lança; migração que falha a meio faz rollback da transacção e não é registada; `pretend` devolve SQL sem executar.
- MigrationCreator: nomes de ficheiro e inferência de tabela; stub create vs update vs blank.
- Seeder: `call()` encadeado com DI.

## Critérios de aceitação
- Com SQLite, MySQL e PostgreSQL, a migração `create_leads_table` do skeleton corre e reverte.
- `composer test/analyse/lint` verdes; `docs/migrations.md` e `docs/seeding.md` escritos, com tabela "Tipos de coluna → SQL por driver" e secção "Limitações do SQLite".

## Não fazer nesta peça
Comandos de consola (07), `squash`, `schema:dump`, geradores/generated columns, índices full-text/espaciais, partições.

## Desvios
(preencher na implementação)
