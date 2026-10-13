<?php

declare(strict_types=1);

namespace Tests;

use Slendie\Foundation\Application;
use Slendie\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createApplication(): Application
    {
        /** @var Application */
        return require __DIR__.'/../bootstrap/app.php';
    }
}
