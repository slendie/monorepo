<?php

declare(strict_types=1);

use Slendie\Http\Request;
use Slendie\Support\Facades\Route;

Route::get('/open', fn () => response()->json(['open' => true]));

Route::middleware('auth:api')->group(function (): void {
    Route::get('/ping', fn (Request $request) => response()->json(['id' => $request->user()?->getAuthIdentifier()]));
    Route::get('/write', fn () => response()->json(['written' => true]))->middleware('ability:leads.write');
});
