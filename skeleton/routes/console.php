<?php

declare(strict_types=1);

use Slendie\Support\Facades\Console;

/*
|--------------------------------------------------------------------------
| Comandos por closure
|--------------------------------------------------------------------------
| Dentro da closure `$this` é o comando (info, warn, ask, table...).
| Argumentos e opções chegam como parâmetros: Console::command('hello {name}', fn (string $name) => ...)
*/

Console::command('inspire', fn () => $this->info('Simplicidade é a sofisticação suprema.'))
    ->purpose('Mostra uma frase inspiradora');
