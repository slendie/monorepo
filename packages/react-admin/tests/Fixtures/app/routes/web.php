<?php

declare(strict_types=1);

use Slendie\Support\Facades\Route;

Route::get('/', fn () => 'home')->name('home');
