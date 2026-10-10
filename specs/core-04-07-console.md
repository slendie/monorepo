# core-04-07 — Console (`php slendie`)

> Peça 07 · Depende de: 01–06 · Sessão única de IA
> Referência obrigatória: `core-02-architecture.md` §5 (CLI)

## Objectivo
Um CLI mínimo, sem dependências (não usar symfony/console), com comandos registáveis por providers, cores ANSI,
argumentos e opções, e os comandos de dia-a-dia: `serve`, `migrate*`, `db:seed`, `make:*`, `route:list`,
`view:clear`, `key:generate`, `vendor:publish`, `list`, `help`.

## Ficheiros a criar (`framework/src/Console/`)
- `Application.php`
  - `__construct(Foundation\Application $app)`; `add(Command|string)`, `resolveCommands(array)`, `all()`, `has(name)`, `find(name)` (exacto; sugestão "Quis dizer `migrate:status`?" por similaridade `levenshtein`/prefixo quando não existe), `run(?array $argv = null): int` (exit code), `call(string $command, array $params = [], ?Output $out = null): int` (uso programático, e por `Command::call()`), `output()`
  - comandos por defeito: `list` (sem args), `help <cmd>`
  - `Foundation\Application::runningInConsole()` true; `terminate()`
- `Command.php` (abstract)
  - `protected string $signature = 'name {arg} {arg?} {arg=default} {arg*} {--opt} {--opt=} {--opt=default} {--O|opt} {--opt=*}'` com descrições inline `{arg : Descrição}`; `protected string $description`; `protected bool $hidden = false`; `protected array $aliases = []`
  - `SignatureParser` → `InputDefinition` (arguments, options)
  - `handle(): int|void` com DI via `Container::call` (parâmetros tipados injectados)
  - entrada: `argument(name)`, `arguments()`, `option(name)`, `options()`, `hasArgument`, `hasOption`
  - saída: `line($text, $style = null)`, `info()` (verde), `comment()` (amarelo), `question()` (ciano), `error()` (branco em vermelho), `warn()`, `newLine(n)`, `table(array $headers, array $rows)` (larguras automáticas, box-drawing simples), `components()`? **não**; `components` substituídos por `task(string $label, Closure)` que imprime `label ........ DONE/FAIL (12ms)`
  - interacção: `ask(question, default)`, `secret(question)` (sem eco; fallback com aviso em Windows sem `stty`), `confirm(question, default = false)`, `choice(question, array $choices, default)`; todas lêem de `STDIN`; em `--no-interaction` ou STDIN não-TTY devolvem default
  - `call(command, params)`, `callSilently()`
  - `verbosity`: `-v`, `-vv`, `-q`; `--ansi/--no-ansi` (auto-detectar TTY e `NO_COLOR`)
  - constantes `SUCCESS = 0`, `FAILURE = 1`, `INVALID = 2`
- `Input.php` — parse de `argv`: argumentos posicionais, `--opt`, `--opt=value`, `--opt value` (só se opção aceita valor), `-O`, `-Ovalue`, arrays; valida obrigatórios e desconhecidos (`InvalidArgumentException` com uso)
- `Output.php` — escreve em `STDOUT`/`STDERR`; `Style` com ANSI; `BufferedOutput` para testes e `callSilently`
- `Commands/`
  - `ServeCommand` — `serve {--host=127.0.0.1} {--port=8000} {--tries=10}`: `php -S host:port -t public` com `passthru`/`proc_open` (streaming de output), porta ocupada → tenta seguinte até `tries`; imprime URL; `Ctrl+C` termina; define `APP_ENV` do `.env`
  - `MigrateCommand` — `migrate {--seed} {--step} {--pretend} {--force}` (`--force` obrigatório em produção, senão `confirm`); cria `database.sqlite` se driver sqlite e ficheiro não existir (com confirmação, ou automático com `--force`)
  - `MigrateRollbackCommand` — `migrate:rollback {--step=1} {--batch=} {--pretend} {--force}`
  - `MigrateResetCommand`, `MigrateRefreshCommand` (`reset`+`run`, `--seed`), `MigrateFreshCommand` (`--seed`, `--force`), `MigrateStatusCommand` (tabela `Migration | Batch | Status` com cores; `--pending` lista só pendentes; exit 1 se houver pendentes com `--pending`), `MigrateInstallCommand` (cria repositório)
  - `DbSeedCommand` — `db:seed {--class=Database\\Seeders\\DatabaseSeeder} {--force}`
  - `DbWipeCommand` — `db:wipe {--force}` (dropAllTables)
  - `MakeControllerCommand` — `make:controller {name} {--resource} {--api} {--invokable} {--model=}`: cria `app/Http/Controllers/{Name}.php` (suporta sub-pastas `Admin/LeadController`), stubs `controller.plain/resource/api/invokable/model.stub`
  - `MakeModelCommand` — `make:model {name} {--migration|-m} {--controller|-c} {--resource|-r} {--all|-a} {--seed|-s}`: `app/Models/{Name}.php`; com `-m` chama `make:migration create_{snake plural}_table --create`
  - `MakeMigrationCommand` — `make:migration {name} {--create=} {--table=} {--path=}` via `MigrationCreator`
  - `MakeMiddlewareCommand` — `app/Http/Middleware/{Name}.php`
  - `MakeProviderCommand` — `app/Providers/{Name}.php` e **acrescenta** à lista em `bootstrap/providers.php` (parse simples do array; se não conseguir, imprime instrução)
  - `MakeCommandCommand` — `make:command {name} {--command=app:name}` → `app/Console/Commands/{Name}.php`
  - `MakeSeederCommand` — `database/seeders/{Name}.php`
  - `MakeRequestCommand` fica para a peça 08 (`FormRequest`)
  - `MakeViewCommand` — `make:view {name}` → `resources/views/{name}.blade.php` com `@extends('layouts.app')`
  - `RouteListCommand` — `route:list {--method=} {--name=} {--path=} {--json}`: tabela `Method | URI | Name | Action | Middleware`
  - `ViewClearCommand` — apaga `storage/framework/views/*.php`
  - `KeyGenerateCommand` — `key:generate {--show} {--force}`: gera `base64:` + 32 bytes; escreve `APP_KEY=` no `.env` (substitui linha existente; cria se não existir); recusa sobrepor chave existente sem `--force`
  - `VendorPublishCommand` — `vendor:publish {--tag=*} {--provider=} {--force} {--all}`: copia ficheiros/pastas registados por `ServiceProvider::publishes()`; lista tags disponíveis quando sem args
  - `AiContextCommand` fica para a peça 09 (depende de PackageDiscovery/Vite); `AuthInstall`/`ReactAdminInstall` são dos packages
  - `OptimizeClearCommand`? **não** (não há caches de config/rotas na v4.0); `EnvCommand` (`env` mostra APP_ENV) trivial, incluir
  - `AboutCommand` — `about`: versão Slendie/PHP, env, debug, driver BD, timezone, URL, providers carregados, packages descobertos (lista vazia até peça 09)
- `ConsoleServiceProvider.php` — regista `Console\Application` singleton, comandos da framework; `ServiceProvider::commands(array)` passa a funcionar (acumula em `Console\Application` quando `runningInConsole()`); carrega `routes/console.php` se existir (função `Slendie\Support\Facades\Artisan`? **Nome**: facade `Console` com `Console::command('nome {arg}', Closure)` para closures-commands como no Laravel, e `Console::call()`)
- `Support/Facades/Console.php`
- `Console/Scheduling/*` **não** (fora de âmbito)
- `Console/GeneratorCommand.php` (abstract para `make:*`): `getStub()`, `qualifyClass()`, `getPath()`, `buildClass()` (substitui `{{ namespace }}`, `{{ class }}`, `{{ rootNamespace }}`, …), `alreadyExists()`, `--force`, cria pastas, imprime `INFO Controller [app/Http/Controllers/X.php] created successfully.`
- `stubs/`: `controller.plain.stub`, `controller.resource.stub`, `controller.api.stub`, `controller.invokable.stub`, `controller.model.stub`, `model.stub`, `middleware.stub`, `provider.stub`, `command.stub`, `seeder.stub`, `view.stub`; publicáveis com `vendor:publish --tag=slendie-stubs` para `stubs/` do projecto (o `GeneratorCommand` prefere stub do projecto se existir)

### Skeleton
- `skeleton/slendie` (sem extensão, executável):
  ```php
  #!/usr/bin/env php
  <?php declare(strict_types=1);
  require __DIR__.'/vendor/autoload.php';
  $app = require __DIR__.'/bootstrap/app.php';
  exit($app->make(Slendie\Console\Application::class)->run($argv));
  ```
- `skeleton/routes/console.php` com exemplo `Console::command('inspire', fn () => $this->info('Simplicidade é a sofisticação suprema.'))`
- `skeleton/app/Console/Commands/.gitkeep`; comandos em `app/Console/Commands` descobertos automaticamente por glob no `ConsoleServiceProvider` (sem precisar de registo)

## Testes (Pest)
- `SignatureParser`: todas as formas de argumentos/opções; descrições; erro em sintaxe inválida.
- `Input`: parsing de argv em todas as formas; obrigatório em falta lança com mensagem de uso; opção desconhecida lança; arrays.
- `Output`/`Style`: ANSI on/off; `table()` alinhamento com acentos (mb_strwidth).
- `Command` interacção com STDIN simulado (stream em memória): `ask/confirm/choice`; `--no-interaction` devolve defaults.
- Comandos (app em fixture com pasta temporária): `make:controller` (plain/resource/api/invokable, sub-pasta, `--force`, já existe), `make:model -m` cria ambos, `make:migration` nomes/inferência, `make:middleware/provider/command/seeder/view`, `make:provider` actualiza `bootstrap/providers.php`; `migrate` (cria sqlite, corre, `--seed`), `migrate:status` tabela e `--pending` exit code, `migrate:rollback --step`, `migrate:fresh --seed`, `migrate --pretend` imprime SQL sem alterar; `db:seed --class`; `route:list` tabela e `--json`, filtros; `view:clear`; `key:generate` escreve `.env` e recusa sem `--force`; `vendor:publish` copia e lista tags; `about`; `list` e `help`; comando desconhecido sugere parecido; `Console::command` closure com DI; `call()` programático devolve exit code; comando em `app/Console/Commands` descoberto.
- `serve`: só testar que monta o comando correcto (`buildCommand()`), não lançar servidor.

## Critérios de aceitação
- No skeleton: `php slendie` lista comandos; `php slendie make:model Lead -mcr` cria model, migração e controller resource; `php slendie migrate` corre; `php slendie route:list` mostra rotas; `php slendie serve` abre `http://127.0.0.1:8000`.
- Zero dependências novas em `framework/composer.json`.
- `composer test/analyse/lint` verdes; `docs/cli.md` com tabela de comandos e `docs/packages.md` secção "Registar comandos".

## Desvios
(preencher na implementação)
