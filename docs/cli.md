# CLI `php slendie`

```bash
php slendie                  # lista comandos
php slendie help migrate
php slendie migrate -v       # verboso; -q silencioso; --no-interaction; --no-ansi
```

## Comandos

| Comando | Faz |
|---|---|
| `serve [--host=127.0.0.1] [--port=8000]` | Servidor de desenvolvimento (`php -S`) |
| `about` | Versões, ambiente, BD, providers, packages |
| `env` | Mostra `APP_ENV` |
| `key:generate [--show] [--force]` | Gera `APP_KEY` no `.env` |
| `migrate [--seed] [--step] [--pretend] [--force]` | Corre migrações pendentes |
| `migrate:status [--pending]` | Estado das migrações (`--pending` → exit 1 se houver) |
| `migrate:rollback [--step=1] [--batch=]` | Reverte |
| `migrate:reset` / `migrate:refresh [--seed]` / `migrate:fresh [--seed]` | Reverte tudo / reverte e corre / apaga tabelas e corre |
| `migrate:install` | Cria a tabela `migrations` |
| `db:seed [--class=]` | Corre seeders |
| `db:wipe [--force]` | Apaga todas as tabelas |
| `make:controller Nome [--resource] [--api] [--invokable] [--model=]` | |
| `make:model Nome [-m] [-c] [-r] [-s] [-a]` | model (+ migração, controller, resource, seeder, tudo) |
| `make:migration nome [--create=] [--table=]` | |
| `make:middleware Nome` / `make:request Nome` / `make:provider Nome` / `make:command Nome` / `make:seeder Nome` / `make:view nome` | |
| `route:list [--method=] [--name=] [--path=] [--json]` | Tabela de rotas |
| `view:clear` | Limpa views compiladas |
| `vendor:publish [--tag=] [--provider=] [--all] [--force]` | Publica ficheiros de packages |
| `package:discover` | Regenera a cache de providers descobertos |
| `ai:context [routes\|schema\|config\|info\|all] [--json]` | Contexto do projecto para agentes de IA |
| `list` / `help <cmd>` | |

Em produção (`APP_ENV=production`), comandos destrutivos pedem confirmação ou `--force`.

## Criar comandos

```bash
php slendie make:command SendReportCommand --command=report:send
```

```php
final class SendReportCommand extends Command
{
    protected string $signature = 'report:send {email : Destinatário} {--days=7 : Período} {--dry-run}';
    protected string $description = 'Envia o relatório de leads';

    public function handle(LeadRepository $leads): int          // DI no handle
    {
        $email = $this->argument('email');
        $days  = (int) $this->option('days');

        if (! $this->confirm("Enviar para {$email}?", true)) {
            return self::FAILURE;
        }

        $this->task('A gerar relatório', fn () => $leads->report($days));
        $this->table(['Estado', 'Total'], [['new', 10], ['won', 3]]);
        $this->info('Enviado.');  $this->warn('x');  $this->error('y');  $this->line('z');

        $name = $this->ask('Nome?', 'default');
        $pass = $this->secret('Password?');
        $opt  = $this->choice('Formato?', ['pdf', 'csv'], 'pdf');

        $this->call('migrate:status');

        return self::SUCCESS;
    }
}
```

Comandos em `app/Console/Commands` são descobertos automaticamente. Em closure, em `routes/console.php`:

```php
use Slendie\Support\Facades\Console;

Console::command('inspire', function () {
    $this->info('Simplicidade é a sofisticação suprema.');
});
```

Sintaxe da assinatura: `{arg}`, `{arg?}`, `{arg=default}`, `{arg*}`, `{--opt}`, `{--opt=}`, `{--opt=default}`,
`{--O|opt}`, `{--opt=*}`, e `{arg : descrição}`.

## Stubs

```bash
php slendie vendor:publish --tag=slendie-stubs      # copia para stubs/ e passa a usar os teus
```
