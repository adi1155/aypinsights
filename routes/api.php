<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('dashboard')->group(function () {
    Route::get('/daily-closing', [DashboardApiController::class, 'dailyClosing']);
    Route::get('/ap', [DashboardApiController::class, 'ap']);
    Route::get('/ar', [DashboardApiController::class, 'ar']);
    Route::get('/expense', [DashboardApiController::class, 'expense']);
    Route::get('/ceo', [DashboardApiController::class, 'ceo']);
});
