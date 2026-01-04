<?php

use App\Http\Controllers\Api\DaemonStatusController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    // Daemon Status Checks
    Route::get('/daemon-status', [DaemonStatusController::class, 'index']);
    Route::get('/email/daemon-status', [DaemonStatusController::class, 'emailStatus']);
});
