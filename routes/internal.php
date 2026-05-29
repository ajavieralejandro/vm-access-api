<?php

use App\Http\Controllers\Api\Internal\AccessValidationController;
use App\Http\Controllers\Api\Internal\InternalHealthController;
use Illuminate\Support\Facades\Route;

Route::get('/health', InternalHealthController::class);
Route::post('/access/validate', AccessValidationController::class);
