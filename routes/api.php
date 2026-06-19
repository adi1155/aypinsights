<?php

use App\Http\Controllers\Api\DashboardApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'throttle:api'])->prefix('dashboard')->group(function () {
    Route::get('/daily-closing', [DashboardApiController::class, 'dailyClosing']);
    Route::get('/ap', [DashboardApiController::class, 'ap']);
    Route::get('/ar', [DashboardApiController::class, 'ar']);
    Route::get('/expense', [DashboardApiController::class, 'expense']);
    Route::get('/loan', [DashboardApiController::class, 'loan']);
    Route::get('/payroll', [DashboardApiController::class, 'payroll']);
    Route::get('/attendance', [DashboardApiController::class, 'attendance']);
    Route::get('/production', [DashboardApiController::class, 'production']);
    Route::get('/ceo', [DashboardApiController::class, 'ceo']);
});
