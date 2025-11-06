<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\IpManagementController;
use App\Http\Controllers\DataChangeController;
use App\Http\Controllers\AlertController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\ApplicationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Redirect root to dashboard
Route::get('/', function () {
    return redirect()->route('dashboard');
});

// Dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Activities
Route::prefix('activities')->name('activities.')->group(function () {
    Route::get('/', [ActivityController::class, 'index'])->name('index');
    Route::get('/{id}', [ActivityController::class, 'show'])->name('show');
    Route::delete('/{id}', [ActivityController::class, 'destroy'])->name('destroy');
});

// Sessions
Route::prefix('sessions')->name('sessions.')->group(function () {
    Route::get('/', [SessionController::class, 'index'])->name('index');
    Route::get('/{id}', [SessionController::class, 'show'])->name('show');
    Route::post('/{id}/logout', [SessionController::class, 'forceLogout'])->name('logout');
    Route::post('/close-idle', [SessionController::class, 'closeIdle'])->name('close-idle');
});

// Security Logs
Route::prefix('security')->name('security.')->group(function () {
    Route::get('/', [SecurityController::class, 'index'])->name('index');
    Route::get('/{id}', [SecurityController::class, 'show'])->name('show');
    Route::post('/{id}/resolve', [SecurityController::class, 'resolve'])->name('resolve');
    Route::post('/{id}/unresolve', [SecurityController::class, 'unresolve'])->name('unresolve');
});

// IP Management
Route::prefix('ip-management')->name('ip-management.')->group(function () {
    Route::get('/', [IpManagementController::class, 'index'])->name('index');
    Route::post('/', [IpManagementController::class, 'store'])->name('store');
    Route::get('/{id}', [IpManagementController::class, 'show'])->name('show');
    Route::put('/{id}', [IpManagementController::class, 'update'])->name('update');
    Route::delete('/{id}', [IpManagementController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/activate', [IpManagementController::class, 'activate'])->name('activate');
    Route::post('/{id}/deactivate', [IpManagementController::class, 'deactivate'])->name('deactivate');
});

// Data Changes
Route::prefix('data-changes')->name('data-changes.')->group(function () {
    Route::get('/', [DataChangeController::class, 'index'])->name('index');
    Route::get('/{id}', [DataChangeController::class, 'show'])->name('show');
});

// Alerts
Route::prefix('alerts')->name('alerts.')->group(function () {
    Route::get('/', [AlertController::class, 'index'])->name('index');
    Route::get('/{id}', [AlertController::class, 'show'])->name('show');
    Route::post('/{id}/read', [AlertController::class, 'markAsRead'])->name('read');
    Route::post('/{id}/resolve', [AlertController::class, 'resolve'])->name('resolve');
    Route::post('/read-all', [AlertController::class, 'markAllAsRead'])->name('read-all');
});

// Statistics
Route::prefix('statistics')->name('statistics.')->group(function () {
    Route::get('/', [StatisticsController::class, 'index'])->name('index');
    Route::get('/application/{id}', [StatisticsController::class, 'byApplication'])->name('by-application');
    Route::get('/export', [StatisticsController::class, 'export'])->name('export');
});

// Applications
Route::prefix('applications')->name('applications.')->group(function () {
    Route::get('/', [ApplicationController::class, 'index'])->name('index');
    Route::post('/', [ApplicationController::class, 'store'])->name('store');
    Route::get('/{id}', [ApplicationController::class, 'show'])->name('show');
    Route::put('/{id}', [ApplicationController::class, 'update'])->name('update');
    Route::delete('/{id}', [ApplicationController::class, 'destroy'])->name('destroy');
    Route::post('/{id}/regenerate-api-key', [ApplicationController::class, 'regenerateApiKey'])->name('regenerate-api-key');
});