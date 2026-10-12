<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest — raiz do monorepo
|--------------------------------------------------------------------------
| O Pest exige uma pasta tests/ na raiz. Os testes reais vivem em
| framework/tests e packages/<nome>/tests (ver phpunit.xml). Este ficheiro
| aplica o estado global limpo a todos eles e expõe os helpers partilhados.
*/

use Slendie\Container\Container;
use Slendie\Support\Env;
use Slendie\Support\Facade;

$root = dirname(__DIR__);

$suites = [
    $root.'/framework/tests',
    ...array_filter(glob($root.'/packages/*/tests') ?: [], 'is_dir'),
];

pest()->beforeEach(function (): void {
    Container::setInstance(null);
    Facade::setFacadeApplication(null);
    Facade::clearResolvedInstances();
    Env::flush();
})->in(...$suites);

if (! function_exists('fixturePath')) {
    /**
     * Caminho para os dados de fixture da framework (framework/tests/_data).
     */
    function fixturePath(string $path = ''): string
    {
        return dirname(__DIR__).'/framework/tests/_data'.($path === '' ? '' : '/'.ltrim($path, '/'));
    }
}
