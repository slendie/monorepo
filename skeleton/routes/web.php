<?php

declare(strict_types=1);

use App\Http\Controllers\ContactController;
use App\Http\Controllers\HomeController;
use Slendie\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/obrigado', [HomeController::class, 'thanks'])->name('thanks');
Route::view('/privacidade', 'privacy')->name('privacy');
Route::view('/termos', 'terms')->name('terms');
