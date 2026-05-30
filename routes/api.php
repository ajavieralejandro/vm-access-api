<?php

use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\V1\AccessValidationController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('v1')->group(function () {
    Route::post('/access/validate', [AccessValidationController::class, 'validate']);
});

Route::prefix('internal')
    ->middleware('internal.api.key')
    ->group(base_path('routes/internal.php'));
