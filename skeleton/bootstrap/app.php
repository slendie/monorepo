<?php

declare(strict_types=1);

use Slendie\Foundation\Application;
use Slendie\Foundation\Exceptions\Handler;
use Slendie\Http\Kernel;

return (new Application(dirname(__DIR__)))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Kernel $kernel): void {
        // $kernel->pushMiddleware(App\Http\Middleware\MeuMiddleware::class);
        // $kernel->appendMiddlewareToGroup('web', App\Http\Middleware\MeuMiddleware::class);
    })
    ->withExceptions(function (Handler $handler): void {
        // $handler->dontReport([App\Exceptions\MinhaExcepcao::class]);
    })
    ->create();
