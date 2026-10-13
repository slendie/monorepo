<?php

declare(strict_types=1);

use Slendie\Auth\Http\Controllers\LoginController;
use Slendie\Auth\Http\Controllers\LogoutController;
use Slendie\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'show'])->middleware('guest')->name('login');
Route::post('/login', [LoginController::class, 'store'])->middleware('guest');
Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');
