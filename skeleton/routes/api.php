<?php

declare(strict_types=1);

use App\Http\Controllers\Api\LeadController;
use Slendie\Support\Facades\Route;

// Prefixo /api e grupo "api" (CORS). A API fica desactivada (503) enquanto API_TOKEN estiver vazio.
Route::middleware('api.token')->group(function (): void {
    Route::apiResource('leads', LeadController::class, ['except' => ['store']]);
});
