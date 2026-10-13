<?php

declare(strict_types=1);

use Slendie\ReactAdmin\Http\Controllers\AdminController;
use Slendie\Support\Facades\Route;

Route::get('/admin/{any?}', AdminController::class)->where('any', '.*')->middleware('auth')->name('admin');
