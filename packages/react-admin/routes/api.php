<?php

declare(strict_types=1);

use Slendie\ReactAdmin\Http\Controllers\Api\LeadController;
use Slendie\ReactAdmin\Http\Controllers\Api\MeController;
use Slendie\ReactAdmin\Http\Controllers\Api\StatsController;
use Slendie\Support\Facades\Route;

// Sessão + CSRF (mesmo domínio): o ReactAdminServiceProvider aplica o grupo "web" + "auth" e o prefixo /api/admin.
Route::get('/me', MeController::class);
Route::get('/stats', StatsController::class);
Route::get('/leads', [LeadController::class, 'index']);
Route::get('/leads/{lead}', [LeadController::class, 'show']);
Route::patch('/leads/{lead}', [LeadController::class, 'update']);
Route::delete('/leads/{lead}', [LeadController::class, 'destroy']);
