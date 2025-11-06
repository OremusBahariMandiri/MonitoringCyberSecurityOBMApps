<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ActivityHubController;
use App\Http\Controllers\Api\IpManagementApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Semua route ini memerlukan API Key authentication
| Header: X-API-Key: your-api-key
|
*/

Route::middleware(['api.key'])->group(function () {

    // Activity Logging
    Route::post('/activities', [ActivityHubController::class, 'logActivity']);

    // Session Management
    Route::prefix('sessions')->group(function () {
        Route::post('/track', [ActivityHubController::class, 'trackSession']);
        Route::post('/logout', [ActivityHubController::class, 'logoutSession']);
    });

    // Security
    Route::prefix('security')->group(function () {
        Route::post('/log', [ActivityHubController::class, 'logSecurityEvent']);
    });

    // Data Changes
    Route::post('/data-changes', [ActivityHubController::class, 'logDataChange']);

    // IP Management
    Route::prefix('ip')->group(function () {
        Route::get('/check/{ip}', [ActivityHubController::class, 'checkIpStatus']);

        // Tambahan route untuk manajemen IP (opsional, untuk API)
        Route::get('/list', [IpManagementApiController::class, 'index']);
        Route::post('/add', [IpManagementApiController::class, 'store']);
        Route::put('/update/{id}', [IpManagementApiController::class, 'update']);
        Route::delete('/delete/{id}', [IpManagementApiController::class, 'destroy']);
        Route::post('/{id}/activate', [IpManagementApiController::class, 'activate']);
        Route::post('/{id}/deactivate', [IpManagementApiController::class, 'deactivate']);
    });

    // Statistics
    Route::prefix('statistics')->group(function () {
        Route::get('/dashboard', [ActivityHubController::class, 'getDashboardStats']);
        Route::get('/activity-trend', [ActivityHubController::class, 'getActivityTrend']);

        // Tambahan route untuk statistik IP
        Route::get('/ip-summary', [ActivityHubController::class, 'getIpStatistics']);
    });
});

/*
|--------------------------------------------------------------------------
| Public Routes (without API Key)
|--------------------------------------------------------------------------
*/

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Activity Hub API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});