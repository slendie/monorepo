<?php

declare(strict_types=1);

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';

$response = $app->handle($request = Slendie\Http\Request::capture());
$response->send();

$app->terminate($request, $response);
