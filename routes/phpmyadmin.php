<?php

use App\Http\Controllers\PhpMyAdminController;
use Illuminate\Support\Facades\Route;

/**
 * phpMyAdmin Integration Routes
 * Requires admin authentication
 */
Route::middleware(['web', 'auth'])->group(function () {
    // phpMyAdmin access route
    Route::get('/admin/database/manager', [PhpMyAdminController::class, 'index'])
        ->name('admin.database.manager')
        ->middleware('admin');

    // phpMyAdmin API routes for dashboard
    Route::prefix('/api/phpmyadmin')->middleware('admin')->group(function () {
        Route::get('/status', [PhpMyAdminController::class, 'status'])
            ->name('api.phpmyadmin.status');

        Route::get('/databases', [PhpMyAdminController::class, 'getDatabases'])
            ->name('api.phpmyadmin.databases');

        Route::get('/database/{name}', [PhpMyAdminController::class, 'getDatabase'])
            ->name('api.phpmyadmin.database');

        Route::post('/query', [PhpMyAdminController::class, 'executeQuery'])
            ->name('api.phpmyadmin.query');

        Route::get('/check', [PhpMyAdminController::class, 'check'])
            ->name('api.phpmyadmin.check');
    });
});
