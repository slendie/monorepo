<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Pest
|--------------------------------------------------------------------------
| Todos os testes de tests/Feature usam a TestCase da aplicação (que arranca a app em modo "testing").
| Para uma base de dados limpa em cada teste acrescente RefreshDatabase:
|
|     uses(Tests\TestCase::class, Slendie\Testing\RefreshDatabase::class)->in('Feature');
*/

use Tests\TestCase;

pest()->extend(TestCase::class)->in('Feature');
