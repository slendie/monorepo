<?php

declare(strict_types=1);

/*
| Cria um projecto com `composer create-project` a partir do skeleton do monorepo (e da framework por path) e verifica que
| fica pronto a usar: `php slendie about` e `composer test` no projecto criado. Precisa de rede (Pest, PHPStan e Pint vêm do
| Packagist), por isso fica no grupo "slow", fora do `composer test`. Corre com: composer test:slow
*/

function skeletonRun(array $command, string $cwd): array
{
    // stderr no mesmo pipe: o Composer escreve muito em stderr e dois pipes lidos em sequência bloqueariam.
    $descriptors = [1 => ['pipe', 'w'], 2 => ['redirect', 1]];
    $process = proc_open($command, $descriptors, $pipes, $cwd, ['COMPOSER_NO_INTERACTION' => '1'] + getenv());

    $output = (string) stream_get_contents($pipes[1]);
    fclose($pipes[1]);

    return [proc_close($process), $output];
}

/** @return list<string>|null */
function composerCommand(): ?array
{
    $lines = explode('
', (string) shell_exec((PHP_OS_FAMILY === 'Windows' ? 'where composer' : 'command -v composer').' 2>&1'));
    $found = trim($lines[0]);

    if ($found === '' || ! is_file($found)) {
        return null;
    }

    $phar = dirname($found).DIRECTORY_SEPARATOR.'composer.phar';

    return is_file($phar) ? [PHP_BINARY, $phar] : [$found];
}

function copyDirectory(string $from, string $to): void
{
    @mkdir($to, 0777, true);

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($from, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST) as $item) {
        $relative = substr($item->getPathname(), strlen($from) + 1);

        if (preg_match('#^(vendor|\.phpunit\.cache)([/\\\\]|$)#', $relative) === 1 || $relative === '.env') {
            continue;
        }

        $item->isDir() ? @mkdir($to.DIRECTORY_SEPARATOR.$relative, 0777, true) : copy($item->getPathname(), $to.DIRECTORY_SEPARATOR.$relative);
    }
}

function removeDirectory(string $directory): void
{
    if (! is_dir($directory)) {
        return;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST) as $item) {
        $item->isDir() && ! $item->isLink() ? @rmdir($item->getPathname()) : @unlink($item->getPathname());
    }

    @rmdir($directory);
}

it('cria um projecto com composer create-project que passa nos seus próprios testes', function (): void {
    $root = dirname(__DIR__, 2);
    $work = sys_get_temp_dir().DIRECTORY_SEPARATOR.'slendie_skeleton_'.bin2hex(random_bytes(4));
    $source = $work.DIRECTORY_SEPARATOR.'source';
    $project = $work.DIRECTORY_SEPARATOR.'meu-site';

    try {
        // O skeleton publicado exige slendie/framework ^4.0 estável; no monorepo só existe 4.x-dev, por isso a cópia de origem aceita dev.
        copyDirectory($root.'/skeleton', $source);
        $composer = json_decode((string) file_get_contents($source.'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
        $composer['minimum-stability'] = 'dev';
        file_put_contents($source.'/composer.json', json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        [$code, $output] = skeletonRun([
            ...composerCommand(), 'create-project', '--no-interaction', '--no-progress', '--stability=dev', '--add-repository',
            '--repository='.json_encode(['type' => 'path', 'url' => $source, 'options' => ['symlink' => false]]),
            '--repository='.json_encode(['type' => 'path', 'url' => $root.'/framework', 'options' => ['symlink' => false]]),
            'slendie/slendie', $project,
        ], sys_get_temp_dir());

        expect($code)->toBe(0, $output)
            ->and(is_file($project.'/.env'))->toBeTrue()
            ->and(is_file($project.'/database/database.sqlite'))->toBeTrue()
            ->and(file_get_contents($project.'/.env'))->toContain('APP_KEY=base64:');

        [$code, $output] = skeletonRun([PHP_BINARY, 'slendie', 'about'], $project);
        expect($code)->toBe(0, $output)->and($output)->toContain('Slendie');

        [$code, $output] = skeletonRun([...composerCommand(), 'test'], $project);
        expect($code)->toBe(0, $output);
    } finally {
        removeDirectory($work);
    }
})->group('slow')->skip(fn () => composerCommand() === null, 'Composer não está disponível.');
