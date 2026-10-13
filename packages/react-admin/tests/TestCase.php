<?php

declare(strict_types=1);

namespace Slendie\ReactAdmin\Tests;

use App\Models\Lead;
use Slendie\Auth\Models\User;
use Slendie\Foundation\Application;
use Slendie\Testing\TestCase as BaseTestCase;

/** TestCase da app de fixture `tests/Fixtures/app` (SQLite em memória, sessão em array, auth + react-admin). */
abstract class TestCase extends BaseTestCase
{
    private ?string $storage = null;

    protected function createApplication(): Application
    {
        // O package assume `App\Models\Lead` (skeleton): a fixture fornece-o.
        if (! class_exists(Lead::class, false)) {
            require_once __DIR__.'/Fixtures/Models/Lead.php';
        }

        /** @var Application $app */
        $app = require __DIR__.'/Fixtures/app/bootstrap/app.php';

        $this->storage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'slendie-react-admin-'.bin2hex(random_bytes(6));
        $app->useStoragePath($this->storage);

        return $app;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        if ($this->storage !== null) {
            self::deleteDirectory($this->storage);
            $this->storage = null;
        }
    }

    private static function deleteDirectory(string $path): void
    {
        if (is_file($path) || is_link($path)) {
            @unlink($path);

            return;
        }

        if (! is_dir($path)) {
            return;
        }

        foreach (glob($path.DIRECTORY_SEPARATOR.'{,.}*', GLOB_BRACE) ?: [] as $item) {
            if (! in_array(basename($item), ['.', '..'], true)) {
                self::deleteDirectory($item);
            }
        }

        @rmdir($path);
    }

    protected function makeUser(string $email = 'ana@x.pt'): User
    {
        return User::create(['name' => 'Ana', 'email' => $email, 'password' => 'secret-123']);
    }

    /** @param array<string, mixed> $overrides */
    protected function makeLead(array $overrides = []): Lead
    {
        return Lead::create([
            'name' => 'Bruno Costa',
            'email' => 'bruno@exemplo.pt',
            'status' => 'new',
            ...$overrides,
        ]);
    }
}
