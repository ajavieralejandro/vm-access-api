<?php

use App\Http\Controllers\Api\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', HealthController::class);

Route::prefix('internal')
    ->middleware('internal.api.key')
    ->group(base_path('routes/internal.php'));
