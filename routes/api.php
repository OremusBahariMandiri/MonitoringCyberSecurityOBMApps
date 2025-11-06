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

Route::middleware(['verify.api.key'])->group(function () {  // <-- PERUBAHAN DISINI

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
        Route::get('/check/{ip}', [IpManagementApiController::class, 'checkStatus']);
        Route::post('/register', [IpManagementApiController::class, 'registerIp']);
        Route::get('/list', [IpManagementApiController::class, 'listIps']);
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

// Tambahkan endpoint tanpa autentikasi untuk pengujian
Route::post('/ip/register-public', [IpManagementApiController::class, 'registerIp']);

Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Activity Hub API is running',
        'timestamp' => now()->toIso8601String(),
    ]);
});