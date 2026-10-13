<?php

declare(strict_types=1);

use Slendie\Foundation\ServiceProvider;
use Slendie\ReactAdmin\Support\Leads;
use Slendie\Tests\Fixtures\Console\ConsoleProject;

const REACT_ADMIN_PACKAGE = __DIR__.'/../..';

beforeEach(function (): void {
    if (! class_exists(App\Models\Lead::class, false)) {
        require_once __DIR__.'/../Fixtures/Models/Lead.php';
    }
});

afterEach(function (): void {
    ConsoleProject::cleanup();
    ServiceProvider::flushPublishes();
    Leads::$model = 'App\\Models\\Lead';
});

/**
 * Um projecto onde `composer require slendie/auth slendie/react-admin` já correu.
 *
 * @param  list<string>  $packages
 * @param  array<string, string>  $files
 */
function adminProject(array $files = [], array $packages = ['auth', 'react-admin']): ConsoleProject
{
    $installed = array_map(
        static fn (string $name): array => json_decode((string) file_get_contents(REACT_ADMIN_PACKAGE."/../{$name}/composer.json"), true),
        $packages,
    );

    return ConsoleProject::make([
        'vendor/composer/installed.json' => (string) json_encode(['packages' => $installed]),
        'routes/web.php' => "<?php\n",
        '.gitignore' => "/vendor/\n.env\n",
        'public/.htaccess' => "RewriteEngine On\n",
        '.env' => "APP_NAME=Teste\nAPP_KEY=base64:dGVzdGluZy1rZXktdGVzdGluZy1rZXktMTIzNDU2Nzg=\n",
        'config/app.php' => "<?php\n\nreturn ['name' => 'Teste', 'env' => 'local', 'debug' => false, 'url' => 'http://localhost', 'key' => env('APP_KEY'), 'timezone' => 'UTC', 'locale' => 'pt', 'providers' => []];\n",
        'config/session.php' => "<?php\n\nreturn ['driver' => 'array'];\n",
        'config/auth.php' => "<?php\n\nreturn ['hashing' => ['driver' => 'bcrypt', 'bcrypt' => ['rounds' => 4]]];\n",
        ...$files,
    ]);
}

it('publica o painel, o package.json, o .gitignore e a migração das notas', function (): void {
    $project = adminProject();

    [$code, $output] = $project->artisan('react-admin:install --no-npm');

    expect($code)->toBe(0)
        ->and($project->has('resources/admin/main.tsx'))->toBeTrue()
        ->and($project->has('resources/admin/pages/LeadDetail.tsx'))->toBeTrue()
        ->and($project->has('resources/admin/api.ts'))->toBeTrue()
        ->and($project->has('resources/admin/styles.css'))->toBeTrue()
        ->and($project->has('vite.config.ts'))->toBeTrue()
        ->and($project->has('tsconfig.json'))->toBeTrue()
        ->and($project->read('vite.config.ts'))->toContain('resources/admin/main.tsx')
        ->and($output)->toContain('npm install && npm run dev')
        ->and($output)->toContain('php slendie serve');

    $package = json_decode($project->read('package.json'), true);
    expect($package['dependencies'])->toHaveKeys(['react', 'react-dom', 'react-router'])
        ->and($package['devDependencies'])->toHaveKeys(['vite', '@vitejs/plugin-react', 'laravel-vite-plugin', 'tailwindcss', '@tailwindcss/vite', 'typescript'])
        ->and($package['scripts'])->toHaveKeys(['dev', 'build'])
        ->and($package['type'])->toBe('module');

    $gitignore = $project->read('.gitignore');
    expect($gitignore)->toContain("/public/build\n")->and($gitignore)->toContain("/public/hot\n")->and($gitignore)->toContain('/vendor/');

    expect($project->read('public/.htaccess'))->toContain('immutable');

    $migrations = glob($project->path('database/migrations/*add_notes_to_leads_table.php')) ?: [];
    expect($migrations)->toHaveCount(1);
});

it('faz merge do package.json existente sem tocar no que o projecto já tem', function (): void {
    $project = adminProject(['package.json' => (string) json_encode([
        'name' => 'meu-site',
        'scripts' => ['dev' => 'meu-dev', 'lint' => 'eslint .'],
        'dependencies' => ['react' => '^18.0.0', 'axios' => '^1.0.0'],
    ])]);

    $project->artisan('react-admin:install --no-npm');
    $package = json_decode($project->read('package.json'), true);

    expect($package['name'])->toBe('meu-site')
        ->and($package['scripts']['dev'])->toBe('meu-dev')
        ->and($package['scripts']['lint'])->toBe('eslint .')
        ->and($package['scripts']['build'])->toContain('vite build')
        ->and($package['dependencies']['react'])->toBe('^18.0.0')
        ->and($package['dependencies']['axios'])->toBe('^1.0.0')
        ->and($package['dependencies'])->toHaveKey('react-router')
        ->and($package['devDependencies'])->toHaveKey('vite');
});

it('não substitui ficheiros do projecto sem --force, e não duplica gitignore, htaccess nem migração', function (): void {
    $project = adminProject(['resources/admin/main.tsx' => '// o meu main']);

    $project->artisan('react-admin:install --no-npm');
    [$code, $output] = $project->artisan('react-admin:install --no-npm');

    expect($code)->toBe(0)
        ->and($project->read('resources/admin/main.tsx'))->toBe('// o meu main')
        ->and($output)->toContain('SKIPPED')
        ->and(substr_count($project->read('.gitignore'), '/public/build'))->toBe(1)
        ->and(substr_count($project->read('public/.htaccess'), 'slendie/react-admin'))->toBe(1)
        ->and(glob($project->path('database/migrations/*add_notes_to_leads_table.php')))->toHaveCount(1);

    [$code] = $project->artisan('react-admin:install --no-npm --force');
    expect($code)->toBe(0)->and($project->read('resources/admin/main.tsx'))->toContain('createRoot');
});

it('não publica a migração se leads.notes já existe', function (): void {
    $project = adminProject();
    $project->artisan('migrate --force');
    $project->app->make(Slendie\Database\DatabaseManager::class)->connection()->getSchemaBuilder()->create('leads', function ($table): void {
        $table->id();
        $table->text('notes')->nullable();
    });

    $project->artisan('react-admin:install --no-npm');

    expect(glob($project->path('database/migrations/*add_notes_to_leads_table.php')))->toBe([]);
});

it('aborta sem slendie/auth instalado, com instrução', function (): void {
    $project = adminProject(packages: ['react-admin']);

    [$code, $output] = $project->artisan('react-admin:install --no-npm');

    expect($code)->toBe(1)
        ->and($output)->toContain('slendie/auth')
        ->and($project->has('resources/admin'))->toBeFalse();
});

it('aborta se o projecto não tem o model Lead', function (): void {
    Leads::$model = 'App\\Models\\Fantasma';
    $project = adminProject();

    [$code, $output] = $project->artisan('react-admin:install --no-npm');

    expect($code)->toBe(1)
        ->and($output)->toContain('App\\Models\\Fantasma')
        ->and($project->has('resources/admin'))->toBeFalse();
});

it('o package declara o provider em extra.slendie.providers e a migração publicada é idempotente', function (): void {
    $manifest = (new Slendie\Foundation\PackageDiscovery())->discover(adminProject()->path('vendor'));

    expect($manifest['providers'])->toContain(Slendie\ReactAdmin\ReactAdminServiceProvider::class);

    $migration = require REACT_ADMIN_PACKAGE.'/database/migrations/2026_01_01_000100_add_notes_to_leads_table.php';
    // Sem tabela `leads` não faz nada (e não falha).
    $project = adminProject();
    $project->artisan('migrate --force');
    expect(fn () => $migration->up())->not->toThrow(Throwable::class);
});

it('as stubs do frontend estão todas presentes', function (): void {
    foreach ([
        'package.json', 'vite.config.ts', 'tsconfig.json',
        'resources/admin/main.tsx', 'resources/admin/App.tsx', 'resources/admin/api.ts', 'resources/admin/router.tsx', 'resources/admin/styles.css',
        'resources/admin/pages/Dashboard.tsx', 'resources/admin/pages/Leads.tsx', 'resources/admin/pages/LeadDetail.tsx', 'resources/admin/pages/NotFound.tsx',
        'resources/admin/components/Layout.tsx', 'resources/admin/components/Sidebar.tsx', 'resources/admin/components/Table.tsx',
        'resources/admin/components/Pagination.tsx', 'resources/admin/components/StatusBadge.tsx', 'resources/admin/components/SearchInput.tsx',
        'resources/admin/components/Empty.tsx', 'resources/admin/components/Spinner.tsx', 'resources/admin/components/Toast.tsx',
        'resources/admin/hooks/useLeads.ts', 'resources/admin/hooks/useLead.ts', 'resources/admin/hooks/useStats.ts',
    ] as $file) {
        expect(REACT_ADMIN_PACKAGE.'/stubs/'.$file)->toBeFile();
    }
});
