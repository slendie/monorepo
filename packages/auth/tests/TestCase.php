<?php

declare(strict_types=1);

namespace Slendie\Auth\Tests;

use Slendie\Auth\Models\User;
use Slendie\Foundation\Application;
use Slendie\Testing\TestCase as BaseTestCase;

/** TestCase da app de fixture `tests/Fixtures/app` (SQLite em memória, sessão em array, só o AuthServiceProvider). */
abstract class TestCase extends BaseTestCase
{
    private ?string $storage = null;

    protected function createApplication(): Application
    {
        /** @var Application $app */
        $app = require __DIR__.'/Fixtures/app/bootstrap/app.php';

        // Pasta de storage própria de cada teste (cache de views, limitador de login).
        $this->storage = sys_get_temp_dir().DIRECTORY_SEPARATOR.'slendie-auth-'.bin2hex(random_bytes(6));
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

    protected function makeUser(string $email = 'ana@x.pt', string $password = 'secret-123', string $name = 'Ana'): User
    {
        return User::create(['name' => $name, 'email' => $email, 'password' => $password]);
    }
}
