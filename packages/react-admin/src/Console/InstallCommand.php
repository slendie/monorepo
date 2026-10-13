<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Console;

use Slendie\Console\Command;
use Slendie\Database\DatabaseManager;
use Slendie\Foundation\Application;
use Slendie\ReactAdmin\Support\Leads;
use Throwable;

final class InstallCommand extends Command
{
    private const string HTACCESS_MARKER = '# slendie/react-admin: cache dos assets compilados';

    protected string $signature = 'react-admin:install
        {--force : Substitui os ficheiros já publicados}
        {--no-npm : Não oferece correr npm install}';

    protected string $description = 'Publica o painel React (resources/admin, vite.config.ts, package.json) e a migração das notas';

    public function handle(Application $app): int
    {
        if (! $app->bound('auth')) {
            $this->error('O painel precisa de slendie/auth. Corre: composer require slendie/auth && php slendie auth:install');

            return self::FAILURE;
        }

        if (! Leads::available()) {
            $this->error('Não encontrei o model '.Leads::$model.'. O painel assume o skeleton da Slendie (ver docs/react-admin.md para outro model).');

            return self::FAILURE;
        }

        $force = $this->option('force') === true;
        $tags = ['react-admin-stubs'];

        if (! $this->hasNotesColumn($app)) {
            $tags[] = 'react-admin-migrations';
        }

        $published = $this->call('vendor:publish', ['--tag' => $tags, '--force' => $force]);

        if ($published !== self::SUCCESS) {
            return $published;
        }

        $vite = $app->basePath('vite.config.ts');

        if (is_file($vite) && ! str_contains((string) file_get_contents($vite), 'resources/admin/main.tsx')) {
            $this->warn("O vite.config.ts do projecto já existia: acrescenta-lhe a entrada 'resources/admin/main.tsx' e os plugins react() e tailwindcss().");
        }

        $this->mergePackageJson($app);
        $this->ignoreBuildArtifacts($app);
        $this->cacheBuildAssets($app);

        $this->newLine();
        $this->info('Painel instalado. Próximos passos:');
        $this->line('  1. <info>php slendie migrate</info>   (coluna leads.notes)');
        $this->line('  2. <info>npm install && npm run dev</info>   (terminal 1)');
        $this->line('  3. <info>php slendie serve</info>   (terminal 2) e abre /admin');

        if ($this->option('no-npm') !== true && $this->npmAvailable() && $this->confirm('Correr npm install agora?', false)) {
            return $this->npmInstall($app);
        }

        return self::SUCCESS;
    }

    private function hasNotesColumn(Application $app): bool
    {
        try {
            $table = (new (Leads::model()))->getTable();

            return $app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->hasColumn($table, 'notes');
        } catch (Throwable) {
            return false;
        }
    }

    /** Acrescenta dependências e scripts do painel ao package.json do projecto, sem tocar no que já lá está. */
    private function mergePackageJson(Application $app): void
    {
        $stub = $this->readJson(dirname(__DIR__, 2).'/stubs/package.json');
        $target = $app->basePath('package.json');

        if (! is_file($target)) {
            file_put_contents($target, $this->encode($stub));
            $this->line('  <info>COPIED</info>  package.json');

            return;
        }

        $current = $this->readJson($target);

        foreach (['dependencies', 'devDependencies', 'scripts'] as $section) {
            $merged = array_merge(
                is_array($stub[$section] ?? null) ? $stub[$section] : [],
                is_array($current[$section] ?? null) ? $current[$section] : [],
            );

            if ($merged !== []) {
                if ($section !== 'scripts') {
                    ksort($merged);
                }

                $current[$section] = $merged;
            }
        }

        $current['type'] ??= $stub['type'] ?? 'module';
        $current['private'] ??= true;

        file_put_contents($target, $this->encode($current));
        $this->line('  <info>MERGED</info>  package.json');
    }

    private function ignoreBuildArtifacts(Application $app): void
    {
        $file = $app->basePath('.gitignore');
        $contents = is_file($file) ? (string) file_get_contents($file) : '';
        $lines = array_map('trim', explode("\n", $contents));
        $missing = array_values(array_filter(['/public/build', '/public/hot', '/node_modules'], static fn (string $entry): bool => ! in_array($entry, $lines, true) && ! in_array($entry.'/', $lines, true)));

        if ($missing === []) {
            return;
        }

        $prefix = $contents === '' || str_ends_with($contents, "\n") ? '' : "\n";
        file_put_contents($file, $contents.$prefix.implode("\n", $missing)."\n");
        $this->line('  <info>UPDATED</info>  .gitignore ('.implode(', ', $missing).')');
    }

    /** Cache longa para /build/* (ficheiros com hash no nome) no Apache. */
    private function cacheBuildAssets(Application $app): void
    {
        $file = $app->publicPath('.htaccess');

        if (! is_file($file) || str_contains((string) file_get_contents($file), self::HTACCESS_MARKER)) {
            return;
        }

        $block = "\n".self::HTACCESS_MARKER."\n"
            ."<IfModule mod_headers.c>\n"
            ."    <FilesMatch \"^build/.*\\.(js|css|woff2?|svg|png|jpg|webp)$\">\n"
            ."        Header set Cache-Control \"public, max-age=31536000, immutable\"\n"
            ."    </FilesMatch>\n"
            ."    <LocationMatch \"^/build/\">\n"
            ."        Header set Cache-Control \"public, max-age=31536000, immutable\"\n"
            ."    </LocationMatch>\n"
            ."</IfModule>\n";

        file_put_contents($file, rtrim((string) file_get_contents($file))."\n".$block);
        $this->line('  <info>UPDATED</info>  public/.htaccess (Cache-Control para /build/*)');
    }

    private function npmAvailable(): bool
    {
        $probe = PHP_OS_FAMILY === 'Windows' ? 'where npm 2>NUL' : 'command -v npm 2>/dev/null';
        exec($probe, $output, $code);

        return $code === 0;
    }

    private function npmInstall(Application $app): int
    {
        $this->line('A correr <info>npm install</info>...');
        passthru('cd '.escapeshellarg($app->basePath()).' && npm install', $code);

        return $code === 0 ? self::SUCCESS : self::FAILURE;
    }

    /** @return array<string, mixed> */
    private function readJson(string $file): array
    {
        $decoded = json_decode((string) file_get_contents($file), true);

        /** @var array<string, mixed> */
        return is_array($decoded) ? $decoded : [];
    }

    /** @param array<string, mixed> $data */
    private function encode(array $data): string
    {
        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)."\n";
    }
}
