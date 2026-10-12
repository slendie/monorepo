<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use Slendie\Support\Facades\Route;

Route::view('/', 'home', ['title' => 'Slendie']);

Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
