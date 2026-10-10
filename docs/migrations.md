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
tabela `migrations` com número de *batch*. `rollback` chama `down()` do último batch.

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

## Tipos de coluna

| Blueprint | SQLite | MySQL | PostgreSQL |
|---|---|---|---|
| `id()` / `bigIncrements()` | INTEGER PK AUTOINCREMENT | BIGINT UNSIGNED AUTO_INCREMENT | BIGSERIAL |
| `increments()` | idem | INT UNSIGNED AUTO_INCREMENT | SERIAL |
| `string(n=255)` | VARCHAR(n) | VARCHAR(n) | VARCHAR(n) |
| `char(n)` | VARCHAR(n) | CHAR(n) | CHAR(n) |
| `text()` / `mediumText()` / `longText()` | TEXT | TEXT / MEDIUMTEXT / LONGTEXT | TEXT |
| `integer()` / `bigInteger()` / `smallInteger()` / `tinyInteger()` | INTEGER | INT / BIGINT / SMALLINT / TINYINT | INTEGER / BIGINT / SMALLINT / SMALLINT |
| `unsigned*()` | INTEGER | … UNSIGNED | (sem unsigned) |
| `boolean()` | TINYINT(1) | TINYINT(1) | BOOLEAN |
| `float()` / `double()` | REAL | DOUBLE | DOUBLE PRECISION |
| `decimal(t, p)` | NUMERIC | DECIMAL(t,p) | DECIMAL(t,p) |
| `date()` | DATE | DATE | DATE |
| `dateTime()` / `timestamp()` | DATETIME | DATETIME / TIMESTAMP | TIMESTAMP |
| `time()` | TIME | TIME | TIME |
| `json()` / `jsonb()` | TEXT | JSON | JSON / JSONB |
| `uuid()` | VARCHAR(36) | CHAR(36) | UUID |
| `enum(name, [...])` | VARCHAR + CHECK | ENUM(...) | VARCHAR + CHECK |
| `binary()` | BLOB | BLOB | BYTEA |
| `ipAddress()` / `macAddress()` | VARCHAR(45) / VARCHAR(17) | idem | INET / MACADDR |
| `foreignId()` / `foreignUuid()` | INTEGER / VARCHAR(36) | BIGINT UNSIGNED / CHAR(36) | BIGINT / UUID |
| `timestamps()` | created_at, updated_at nullable | | |
| `softDeletes()` | deleted_at nullable | | |
| `rememberToken()` | VARCHAR(100) nullable | | |

Modificadores: `nullable()`, `default($v)` (aceita `DB::raw('CURRENT_TIMESTAMP')`), `useCurrent()`,
`useCurrentOnUpdate()` (MySQL), `unsigned()`, `unique()`, `index()`, `primary()`, `comment()`, `after()`/`first()`
(MySQL), `change()`.

Índices e chaves: `primary()`, `unique()`, `index()`, `foreign('user_id')->references('id')->on('users')->cascadeOnDelete()`,
`->nullOnDelete()`, `->restrictOnDelete()`, `->onUpdate('cascade')`, `dropPrimary/Unique/Index/Foreign`.

## Limitações do SQLite
- `change()` lança excepção (SQLite não altera tipos de coluna). Cria uma tabela nova e copia.
- `dropForeign()` não é suportado.
- `after()`/`first()` são ignorados.
- `dropColumn`/`renameColumn` requerem SQLite ≥ 3.35 (incluído no PHP 8.5).

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

Packages registam pastas com `loadMigrationsFrom()`; correm junto com as do projecto. Para copiar para o projecto:
`php slendie vendor:publish --tag=auth-migrations`.
