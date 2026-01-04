<?php

use App\Http\Controllers\SecurityDashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:web', 'admin'])->group(function () {
    // Security Dashboard
    Route::get('/security/dashboard', [SecurityDashboardController::class, 'index'])->name('security.dashboard');
    Route::get('/security/audit-logs', [SecurityDashboardController::class, 'auditLogs'])->name('security.audit-logs');
    Route::get('/security/ip-whitelist', fn () => \Inertia\Inertia::render('Security/IpWhitelist'))->name('security.ip-whitelist');
    Route::get('/security/firewall-policies', fn () => \Inertia\Inertia::render('Security/FirewallPolicies'))->name('security.firewall-policies');

    // Policy Management
    Route::prefix('api/security')->group(function () {
        Route::get('/policy', [SecurityDashboardController::class, 'getPolicy']);
        Route::put('/policy', [SecurityDashboardController::class, 'updatePolicy']);
        Route::post('/policy/firewall/toggle', [SecurityDashboardController::class, 'toggleFirewall']);
        Route::post('/policy/brute-force/toggle', [SecurityDashboardController::class, 'toggleBruteForce']);
        Route::post('/policy/ssl/toggle', [SecurityDashboardController::class, 'toggleSslEnforcement']);
        Route::get('/policy/headers', [SecurityDashboardController::class, 'getSecurityHeaders']);
        Route::put('/policy/headers', [SecurityDashboardController::class, 'updateSecurityHeaders']);

        // Brute Force Management
        Route::get('/brute-force/metrics', [SecurityDashboardController::class, 'getBruteForceMetrics']);
        Route::get('/brute-force/attempts', [SecurityDashboardController::class, 'listAttempts']);
        Route::get('/brute-force/attempts/{ip}', [SecurityDashboardController::class, 'getIpAttempts']);
        Route::post('/brute-force/block', [SecurityDashboardController::class, 'blockIp']);
        Route::post('/brute-force/unblock', [SecurityDashboardController::class, 'unblockIp']);
        Route::post('/brute-force/clear-expired', [SecurityDashboardController::class, 'clearExpiredBlocks']);

        // IP Whitelist Management
        Route::get('/whitelist', [SecurityDashboardController::class, 'getWhitelist']);
        Route::get('/whitelist/reason/{reason}', [SecurityDashboardController::class, 'getWhitelistByReason']);
        Route::post('/whitelist/add', [SecurityDashboardController::class, 'addToWhitelist']);
        Route::delete('/whitelist/{id}', [SecurityDashboardController::class, 'removeFromWhitelist']);
        Route::post('/whitelist/sync-cloudflare', [SecurityDashboardController::class, 'syncCloudflareIps']);

        // Dashboard Overview
        Route::get('/overview', [SecurityDashboardController::class, 'overview']);
    });
});
