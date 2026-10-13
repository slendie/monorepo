<?php

declare(strict_types=1);

use Slendie\Http\Request;
use Slendie\Support\Facades\Route;

Route::get('/', fn () => 'home')->name('home');
Route::get('/admin', fn () => 'admin: '.auth()->user()?->getAttribute('name'))->middleware('auth');
Route::get('/whoami', fn (Request $request) => response()->json(['id' => $request->user()?->getAuthIdentifier()]));
Route::view('/directives', 'directives');
