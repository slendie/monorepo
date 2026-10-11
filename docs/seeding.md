# Seeders

```bash
php slendie make:seeder LeadSeeder
php slendie db:seed                              # Database\Seeders\DatabaseSeeder
php slendie db:seed --class=LeadSeeder
php slendie migrate:fresh --seed
```

```php
<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Lead;
use Slendie\Database\Seeders\Seeder;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([LeadSeeder::class]);
    }
}

final class LeadSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Ana', 'Bruno', 'Carla'] as $i => $name) {
            Lead::create(['name' => $name, 'email' => mb_strtolower($name)."@exemplo.pt", 'status' => 'new']);
        }
    }
}
```

Não há *factories* nem Faker na Slendie. Para dados aleatórios usa `Str::random()`, `random_int()` ou instala
`fakerphp/faker` como dependência de desenvolvimento do teu projecto.

Em testes: `$this->seed()` ou `$this->seed(LeadSeeder::class)`, ou `protected bool $seed = true` com `RefreshDatabase`.

## Injecção de dependências e encadeamento

O `run()` não tem parâmetros declarados na classe base: pede o que precisares e o container injecta
(tipos resolvíveis ou com valor por omissão).

```php
final class LeadSeeder extends Seeder
{
    public function run(Connection $db, int $count = 3): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $db->table('leads')->insert(['name' => "Lead {$i}", 'email' => "lead{$i}@exemplo.pt"]);
        }
    }
}

$this->call(LeadSeeder::class);                        // ou call([A::class, B::class]) — por esta ordem
$this->callWith(LeadSeeder::class, ['count' => 10]);   // parâmetros nomeados para o run()
```

`call()` só aceita subclasses de `Seeder` e cria cada uma através do container (`Container::make`). Um seeder sem
`run()` lança `LogicException`. No skeleton, `database/seeders/DatabaseSeeder.php` chama `LeadSeeder` (3 leads de exemplo).
