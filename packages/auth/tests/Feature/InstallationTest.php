<?php

declare(strict_types=1);

use Slendie\Database\DatabaseManager;
use Slendie\Foundation\ServiceProvider;
use Slendie\Http\Request;
use Slendie\Tests\Fixtures\Console\ConsoleProject;
use Slendie\View\Compiler\BladeCompiler;

afterEach(function (): void {
    ConsoleProject::cleanup();
    ServiceProvider::flushPublishes();
    BladeCompiler::flushConditions();
});

const AUTH_PACKAGE = __DIR__.'/../..';

/**
 * Um projecto onde `composer require slendie/auth` já correu: o `installed.json` declara o package.
 *
 * @param  array<string, string>  $files
 */
function authProject(array $files = []): ConsoleProject
{
    $composer = json_decode((string) file_get_contents(AUTH_PACKAGE.'/composer.json'), true);
    $installed = json_encode(['packages' => [$composer]]);

    return ConsoleProject::make([
        'vendor/composer/installed.json' => (string) $installed,
        'routes/web.php' => "<?php\n",
        '.env' => "APP_NAME=Teste\nAPP_KEY=base64:dGVzdGluZy1rZXktdGVzdGluZy1rZXktMTIzNDU2Nzg=\n",
        'config/app.php' => "<?php\n\nreturn ['name' => 'Teste', 'env' => 'local', 'debug' => false, 'url' => 'http://localhost', 'key' => env('APP_KEY'), 'timezone' => 'UTC', 'locale' => 'pt', 'providers' => []];\n",
        'config/session.php' => "<?php\n\nreturn ['driver' => 'array'];\n",
        'config/auth.php' => "<?php\n\nreturn ['hashing' => ['driver' => 'bcrypt', 'bcrypt' => ['rounds' => 4]]];\n",
        ...$files,
    ]);
}

/** @return list<string> */
function migrationFiles(ConsoleProject $project): array
{
    return array_map('basename', glob($project->path('database/migrations/*.php')) ?: []);
}

function dbTable(ConsoleProject $project, string $table): Slendie\Database\Query\Builder
{
    return $project->app->make(DatabaseManager::class)->connection()->table($table);
}

// -----------------------------------------------------------------------------
// auth:install
// -----------------------------------------------------------------------------

it('auth:install publica config, model e migrações, migra e explica como proteger rotas', function (): void {
    $project = authProject();
    // O projecto ainda não tem config/auth.php próprio: remove o de teste para ver a publicação.
    unlink($project->path('config/auth.php'));

    [$code, $output] = $project->artisan('auth:install');

    expect($code)->toBe(0)
        ->and($project->has('config/auth.php'))->toBeTrue()
        ->and($project->read('config/auth.php'))->toContain("'guards'")
        ->and($project->has('app/Models/User.php'))->toBeTrue()
        ->and($project->read('app/Models/User.php'))->toContain('namespace App\Models;')
        ->and($project->read('app/Models/User.php'))->toContain('use Authenticatable;')
        ->and(migrationFiles($project))->toHaveCount(2)
        ->and(migrationFiles($project)[0])->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_users_table\.php$/')
        ->and(migrationFiles($project)[1])->toMatch('/^\d{4}_\d{2}_\d{2}_\d{6}_create_personal_access_tokens_table\.php$/')
        ->and($output)->toContain("->middleware('auth')")
        ->and($output)->toContain('/login');

    $tables = $project->app->make(DatabaseManager::class)->connection()->getSchemaBuilder()->getTables();
    expect($tables)->toContain('users', 'personal_access_tokens');
});

it('auth:install é idempotente: não duplica migrações nem substitui ficheiros do projecto', function (): void {
    $project = authProject();
    $project->put('app/Models/User.php', "<?php\n// o meu User\n");

    $project->artisan('auth:install');
    $first = migrationFiles($project);
    [$code, $output] = $project->artisan('auth:install');

    expect($code)->toBe(0)
        ->and(migrationFiles($project))->toBe($first)
        ->and($project->read('app/Models/User.php'))->toContain('o meu User')
        ->and($output)->toContain('SKIPPED');
});

it('as migrações do package e as publicadas coexistem (a segunda a correr não falha)', function (): void {
    $project = authProject();

    [$code] = $project->artisan('migrate --force');
    expect($code)->toBe(0);

    [$code] = $project->artisan('auth:install');
    expect($code)->toBe(0);
});

it('auth:install --no-migrate só publica', function (): void {
    $project = authProject();

    [$code, $output] = $project->artisan('auth:install --no-migrate');

    expect($code)->toBe(0)
        ->and($project->has('database/database.sqlite'))->toBeFalse()
        ->and(migrationFiles($project))->toHaveCount(2)
        ->and($output)->not->toContain('Migrating');
});

// -----------------------------------------------------------------------------
// make:user e auth:token
// -----------------------------------------------------------------------------

it('make:user cria o utilizador com password em bcrypt', function (): void {
    $project = authProject();
    $project->artisan('migrate --force');

    [$code, $output] = $project->artisan('make:user --name=Ana --email=ana@x.pt --password=secret-123');
    $row = dbTable($project, 'users')->where('email', 'ana@x.pt')->first();

    expect($code)->toBe(0)
        ->and($output)->toContain('ana@x.pt')
        ->and($row['name'])->toBe('Ana')
        ->and($row['password'])->toStartWith('$2y$')
        ->and(password_verify('secret-123', $row['password']))->toBeTrue();
});

it('make:user recusa email repetido, inválido, password curta e falta de dados', function (): void {
    $project = authProject();
    $project->artisan('migrate --force');
    $project->artisan('make:user --name=Ana --email=ana@x.pt --password=secret-123');

    [$code, $output] = $project->artisan('make:user --name=Outra --email=ana@x.pt --password=secret-123');
    expect($code)->toBe(1)->and($output)->toContain('Já existe');

    [$code, $output] = $project->artisan('make:user --name=Bia --email=nao-e-email --password=secret-123');
    expect($code)->toBe(1)->and($output)->toContain('não é válido');

    [$code, $output] = $project->artisan('make:user --name=Bia --email=bia@x.pt --password=curta');
    expect($code)->toBe(1)->and($output)->toContain('8 caracteres');

    [$code, $output] = $project->artisan('make:user');
    expect($code)->toBe(1)->and($output)->toContain('obrigatórios');

    expect(dbTable($project, 'users')->count())->toBe(1);
});

it('make:user pergunta os dados em modo interactivo (password sem eco, quando o terminal o permite)', function (): void {
    $project = authProject();
    $project->artisan('migrate --force');
    $project->console->setInteractive(true);
    $project->console->setInputStream(fopen('data://text/plain,Bia%0Abia@x.pt%0Asecret-456%0A', 'r'));

    [$code] = $project->artisan('make:user');

    expect($code)->toBe(0)
        ->and(dbTable($project, 'users')->where('email', 'bia@x.pt')->count())->toBe(1);
})->skip(fn () => ! method_exists(Slendie\Console\Application::class, 'setInputStream'), 'sem setInputStream');

it('auth:token cria um token que funciona e mostra-o uma vez', function (): void {
    $project = authProject();
    $project->artisan('migrate --force');
    $project->artisan('make:user --name=Ana --email=ana@x.pt --password=secret-123');

    [$code, $output] = $project->artisan('auth:token ana@x.pt --name=ci --abilities=leads.read,leads.write');
    preg_match('/(\d+\|[A-Za-z0-9]{40})/', $output, $match);
    $row = dbTable($project, 'personal_access_tokens')->first();

    expect($code)->toBe(0)
        ->and($match)->not->toBe([])
        ->and($row['name'])->toBe('ci')
        ->and(json_decode($row['abilities'], true))->toBe(['leads.read', 'leads.write'])
        ->and($row['token'])->toBe(hash('sha256', explode('|', $match[1])[1]));
});

it('auth:token usa todas as abilities por omissão e falha para emails desconhecidos', function (): void {
    $project = authProject();
    $project->artisan('migrate --force');
    $project->artisan('make:user --name=Ana --email=ana@x.pt --password=secret-123');

    $project->artisan('auth:token ana@x.pt');
    expect(json_decode(dbTable($project, 'personal_access_tokens')->first()['abilities'], true))->toBe(['*']);

    [$code, $output] = $project->artisan('auth:token fantasma@x.pt');
    expect($code)->toBe(1)->and($output)->toContain('Não existe');
});

// -----------------------------------------------------------------------------
// Overrides pelo projecto
// -----------------------------------------------------------------------------

it('as views publicadas em resources/views/vendor/auth sobrepõem as do package', function (): void {
    $project = authProject(['resources/views/vendor/auth/login.blade.php' => 'LOGIN DO PROJECTO']);

    $response = $project->app->handle(Request::create('/login'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toBe('LOGIN DO PROJECTO');
});

it('sem override, o login do package renderiza em pt', function (): void {
    $response = authProject()->app->handle(Request::create('/login'));

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getContent())->toContain('Lembrar-me neste dispositivo')
        ->and($response->getContent())->toContain('<form method="POST"');
});

it('lang/vendor/auth sobrepõe as mensagens do package', function (): void {
    $project = authProject(['lang/vendor/auth/pt/auth.php' => "<?php\n\nreturn ['failed' => 'Falhou à moda do projecto'];\n"]);
    $project->app->bootstrap();

    expect(__('auth::auth.failed'))->toBe('Falhou à moda do projecto')
        // As outras chaves continuam a vir do package.
        ->and(__('auth::auth.login'))->toBe('Entrar');
});

it('as mensagens existem em pt e en com as mesmas chaves', function (): void {
    $pt = require AUTH_PACKAGE.'/lang/pt/auth.php';
    $en = require AUTH_PACKAGE.'/lang/en/auth.php';

    expect(array_keys($pt))->toBe(array_keys($en))
        ->and(array_keys($pt))->toContain('failed', 'throttle', 'password');
});

it('as rotas do package ficam nomeadas e visíveis em route:list', function (): void {
    [$code, $output] = authProject()->artisan('route:list');

    expect($code)->toBe(0)
        ->and($output)->toContain('login')
        ->and($output)->toContain('logout');
});

it('o package declara o provider em extra.slendie.providers e o PackageDiscovery encontra-o', function (): void {
    $project = authProject();

    $manifest = (new Slendie\Foundation\PackageDiscovery())->discover($project->path('vendor'));

    expect($manifest['providers'])->toBe([Slendie\Auth\AuthServiceProvider::class]);
});
