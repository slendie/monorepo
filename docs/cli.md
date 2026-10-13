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
| `make:middleware Nome` / `make:provider Nome` / `make:command Nome [--command=app:nome]` / `make:seeder Nome` / `make:view nome` | `make:provider` também regista o provider em `bootstrap/providers.php` |
| `make:request Nome` | Chega com a validação (peça 08) |
| `route:list [--method=] [--name=] [--path=] [--json]` | Tabela de rotas |
| `view:clear` | Limpa views compiladas |
| `vendor:publish [--tag=] [--provider=] [--all] [--force]` | Publica ficheiros de packages |
| `package:discover` | Regenera a cache de providers descobertos (chega com os packages, peça 09) |
| `ai:context [routes\|schema\|config\|info\|all] [--json]` | Contexto do projecto para agentes de IA (peça 09) |
| `ai:agents-file [--force]` | Escreve o `AGENTS.md` (o que a framework tem e não tem) a partir de `AGENTS.md.stub` (peça 11) |
| `list` / `help <cmd>` | |

Em produção (`APP_ENV=production`), comandos destrutivos pedem confirmação ou `--force`. Sem terminal (CI, pipe) a
confirmação responde "não" e o comando é cancelado; `--force` dispensa-a.

## Opções globais e códigos de saída

| Opção | Efeito |
|---|---|
| `-h`, `--help` | Ajuda do comando (o mesmo que `help <comando>`) |
| `-q`, `--quiet` | Sem output (os erros continuam a aparecer em STDERR) |
| `-v`, `-vv` | Output verboso / muito verboso (erros com classe, ficheiro e trace) |
| `-n`, `--no-interaction` | Nunca pergunta: `ask`/`confirm`/`choice` devolvem o valor por omissão |
| `--ansi`, `--no-ansi` | Força ou desliga as cores (por omissão: só num terminal e sem `NO_COLOR`) |
| `-V`, `--version` | Versão da framework |

`0` sucesso · `1` falha (o comando devolveu `FAILURE`, excepção ou comando inexistente) · `2` entrada inválida
(argumento em falta, opção desconhecida). Comandos desconhecidos sugerem os parecidos ("Quis dizer `migrate:status`?").

Atalhos de opções podem agrupar-se (`make:model Lead -mcr`) e levar valor (`-Ovalor`, `-O valor`, `-O=valor`). `--` termina as
opções. Opções com valor aceitam `--opt=valor` e `--opt valor`; as repetíveis (`{--tag=*}`) acumulam.

Num terminal Windows sem suporte UTF-8, as tabelas desenham-se com ASCII (`+---+`).

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

`handle()` pode devolver um `int` (código de saída), `void` ou `null` (= sucesso). Dentro do comando também existem
`table()`, `task()`, `newLine()`, `confirm()`, `callSilently()` e `isVerbose()`:

```php
$this->table(['Estado', 'Total'], [['new', 10], ['won', 3]]);       // larguras automáticas (mb_strwidth)
$this->task('A gerar relatório', fn () => $leads->report());        // A gerar relatório ...... DONE (12ms)
$this->line('Só com -v', null, Output::VERBOSITY_VERBOSE);
```

Comandos em `app/Console/Commands` (incluindo sub-pastas) são descobertos automaticamente. Em closure, em `routes/console.php`
(só carregado na consola); `$this` é o comando, os argumentos e opções chegam como parâmetros com o mesmo nome e o resto
é injectado pelo container:

```php
use Slendie\Support\Facades\Console;

Console::command('inspire', function () {
    $this->info('Simplicidade é a sofisticação suprema.');
})->purpose('Mostra uma frase inspiradora');

Console::command('lead:count {status=new}', fn (string $status, Connection $db) => $this->line(
    (string) $db->table('leads')->where('status', $status)->count(),
));
```

Comandos de packages registam-se no `boot()` do provider com `$this->commands([...])` (ver [packages](packages.md)).

Sintaxe da assinatura: `{arg}`, `{arg?}`, `{arg=default}`, `{arg*}`, `{--opt}`, `{--opt=}`, `{--opt=default}`,
`{--O|opt}`, `{--opt=*}`, e `{arg : descrição}`.

## Chamar comandos a partir do código (e nos testes)

```php
$exit = Console::call('make:model', ['name' => 'Lead', '--migration' => true]);   // argumentos por nome, opções com --
$exit = $this->call('migrate:status');                                             // dentro de um comando (partilha a saída)
$exit = $this->callSilently('migrate', ['--force' => true]);                       // sem output

$out = new Slendie\Console\BufferedOutput();
Console::call('route:list', ['--json' => true], $out);
$json = $out->fetch();
```

Nos testes, `$console->setInputStream(fopen('php://memory', 'w+'))` simula o teclado e `$console->setInteractive(false)` desliga as perguntas.

## Stubs

```bash
php slendie vendor:publish --tag=slendie-stubs      # copia para stubs/ e passa a usar os teus
```

Os `make:*` usam o stub de `stubs/` do projecto quando existe (senão o da framework). Marcadores: `{{ namespace }}`,
`{{ class }}`, `{{ rootNamespace }}` e, conforme o comando, `{{ model }}`, `{{ modelVariable }}`, `{{ namespacedModel }}`,
`{{ command }}`, `{{ view }}`, `{{ table }}`. O namespace raiz (`App\`) vem do PSR-4 de `app/` no `composer.json`.
