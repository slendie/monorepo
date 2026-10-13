<?php

declare(strict_types=1);

namespace Slendie\Auth\Console;

use Slendie\Console\Command;

final class InstallCommand extends Command
{
    protected string $signature = 'auth:install
        {--force : Substitui os ficheiros já publicados}
        {--no-migrate : Não corre as migrações}';

    protected string $description = 'Publica a configuração, as migrações e o model User; migra e oferece criar o primeiro utilizador';

    public function handle(): int
    {
        $published = $this->call('vendor:publish', [
            '--tag' => ['auth-config', 'auth-migrations', 'auth-model'],
            '--force' => $this->option('force') === true,
        ]);

        if ($published !== self::SUCCESS) {
            return $published;
        }

        if ($this->option('no-migrate') !== true) {
            $migrated = $this->call('migrate', ['--force' => true]);

            if ($migrated !== self::SUCCESS) {
                return $migrated;
            }
        }

        if ($this->confirm('Criar o primeiro utilizador agora?', false)) {
            $this->call('make:user');
        }

        $this->newLine();
        $this->info('Autenticação instalada. Login em /login (utilizadores novos: php slendie make:user).');
        $this->line("Proteja o painel com <info>->middleware('auth')</info> e a API com <info>->middleware('auth:api')</info>.");

        return self::SUCCESS;
    }
}
