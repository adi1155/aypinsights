<?php

use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('login'));

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/ceo', [DashboardController::class, 'ceo'])->name('dashboard.ceo')->middleware('role:CEO|CFO|Director');
    Route::get('/daily-closing', [DashboardController::class, 'dailyClosing'])->name('dashboard.closing')->middleware('permission:view daily closing');
    Route::get('/ap', [DashboardController::class, 'ap'])->name('dashboard.ap')->middleware('permission:view ap dashboard');
    Route::get('/ar', [DashboardController::class, 'ar'])->name('dashboard.ar')->middleware('permission:view ar dashboard');
    Route::get('/expense', [DashboardController::class, 'expense'])->name('dashboard.expense')->middleware('permission:view expense dashboard');
    Route::get('/payroll', [DashboardController::class, 'payroll'])->name('dashboard.payroll')->middleware('permission:view payroll dashboard');
    Route::get('/attendance', [DashboardController::class, 'attendance'])->name('dashboard.attendance')->middleware('permission:view attendance dashboard');
    Route::get('/production', [DashboardController::class, 'production'])->name('dashboard.production')->middleware('permission:view production dashboard');
    Route::get('/export/{type}/{dashboard}', [DashboardController::class, 'export'])->name('dashboard.export');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingsController::class, 'update'])->name('settings.update');
    Route::get('/scheduled-reports', [SettingsController::class, 'scheduledReports'])->name('settings.scheduled');
    Route::post('/scheduled-reports', [SettingsController::class, 'storeScheduledReport'])->name('settings.scheduled.store');

    Route::middleware('role:CEO|CFO')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);
        Route::get('companies', [CompanyController::class, 'index'])->name('companies.index');
        Route::post('companies', [CompanyController::class, 'store'])->name('companies.store');
        Route::get('branches', [BranchController::class, 'index'])->name('branches.index');
        Route::post('branches', [BranchController::class, 'store'])->name('branches.store');
        Route::get('audit-logs', [AuditLogController::class, 'index'])->name('audit.index');
    });
});
