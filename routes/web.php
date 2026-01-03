<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\CronJobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DatabaseController;
use App\Http\Controllers\DnsZoneController;
use App\Http\Controllers\EmailAccountController;
use App\Http\Controllers\FileManagerController;
use App\Http\Controllers\FirewallController;
use App\Http\Controllers\FtpUserController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\WebDomainController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/web-domains', [WebDomainController::class, 'index'])->name('web-domains.index');
    Route::post('/web-domains', [WebDomainController::class, 'store'])->name('web-domains.store');
    Route::patch('/web-domains/{webDomain}', [WebDomainController::class, 'update'])->name('web-domains.update');
    Route::post('/web-domains/{webDomain}/toggle-ssl', [WebDomainController::class, 'toggleSsl'])->name('web-domains.toggle-ssl');
    Route::post('/web-domains/{webDomain}/request-ssl', [WebDomainController::class, 'requestSsl'])->name('web-domains.request-ssl');
    Route::delete('/web-domains/{webDomain}', [WebDomainController::class, 'destroy'])->name('web-domains.destroy');

    Route::get('/databases', [DatabaseController::class, 'index'])->name('databases.index');
    Route::get('/databases/create', [DatabaseController::class, 'create'])->name('databases.create');
    Route::post('/databases', [DatabaseController::class, 'store'])->name('databases.store');
    Route::delete('/databases/{database}', [DatabaseController::class, 'destroy'])->name('databases.destroy');

    Route::get('/ftp-users', [FtpUserController::class, 'index'])->name('ftp-users.index');
    Route::get('/ftp-users/create', [FtpUserController::class, 'create'])->name('ftp-users.create');
    Route::post('/ftp-users', [FtpUserController::class, 'store'])->name('ftp-users.store');
    Route::delete('/ftp-users/{ftpUser}', [FtpUserController::class, 'destroy'])->name('ftp-users.destroy');

    Route::get('/cron-jobs', [CronJobController::class, 'index'])->name('cron-jobs.index');
    Route::post('/cron-jobs', [CronJobController::class, 'store'])->name('cron-jobs.store');
    Route::patch('/cron-jobs/{cronJob}', [CronJobController::class, 'update'])->name('cron-jobs.update');
    Route::delete('/cron-jobs/{cronJob}', [CronJobController::class, 'destroy'])->name('cron-jobs.destroy');

    Route::get('/dns-zones', [DnsZoneController::class, 'index'])->name('dns-zones.index');
    Route::post('/dns-zones', [DnsZoneController::class, 'store'])->name('dns-zones.store');
    Route::get('/dns-zones/{dnsZone}', [DnsZoneController::class, 'show'])->name('dns-zones.show');
    Route::put('/dns-zones/{dnsZone}/records', [DnsZoneController::class, 'updateRecords'])->name('dns-zones.update-records');
    Route::delete('/dns-zones/{dnsZone}', [DnsZoneController::class, 'destroy'])->name('dns-zones.destroy');
    Route::get('/email-accounts', [EmailAccountController::class, 'index'])->name('email-accounts.index');
    Route::post('/email-accounts', [EmailAccountController::class, 'store'])->name('email-accounts.store');
    Route::delete('/email-accounts/{emailAccount}', [EmailAccountController::class, 'destroy'])->name('email-accounts.destroy');

    Route::get('/file-manager', [FileManagerController::class, 'index'])->name('file-manager.index');
    Route::get('/file-manager/list', [FileManagerController::class, 'list'])->name('file-manager.list');
    Route::get('/file-manager/read', [FileManagerController::class, 'read'])->name('file-manager.read');
    Route::post('/file-manager/write', [FileManagerController::class, 'write'])->name('file-manager.write');
    Route::delete('/file-manager/delete', [FileManagerController::class, 'delete'])->name('file-manager.delete');
    Route::post('/file-manager/create-directory', [FileManagerController::class, 'createDirectory'])->name('file-manager.create-directory');
    Route::post('/file-manager/upload', [FileManagerController::class, 'upload'])->name('file-manager.upload');
    Route::post('/file-manager/rename', [FileManagerController::class, 'rename'])->name('file-manager.rename');
    Route::get('/file-manager/download', [FileManagerController::class, 'download'])->name('file-manager.download');

    Route::get('/logs', [LogController::class, 'index'])->name('logs.index');
    Route::get('/logs/fetch', [LogController::class, 'fetch'])->name('logs.fetch');

    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');
    Route::get('/services/status', [ServiceController::class, 'status'])->name('services.status');
    Route::get('/services/logs/{service}', [ServiceController::class, 'getLogs'])->name('services.logs');
    // Using POST for restart as it's an action
    Route::post('/services/restart', [ServiceController::class, 'restart'])->name('services.restart');

    Route::get('/backups', [BackupController::class, 'index'])->name('backups.index');
    Route::post('/backups', [BackupController::class, 'store'])->name('backups.store');
    Route::get('/backups/{backup}/download', [BackupController::class, 'download'])->name('backups.download');
    Route::post('/backups/{backup}/restore', [BackupController::class, 'restore'])->name('backups.restore');
    Route::delete('/backups/{backup}', [BackupController::class, 'destroy'])->name('backups.destroy');

    Route::get('/monitoring', [MonitoringController::class, 'index'])->name('monitoring.index');
    Route::get('/monitoring/stats', [MonitoringController::class, 'stats'])->name('monitoring.stats');

    Route::get('/firewall', [FirewallController::class, 'index'])->name('firewall.index');
    Route::post('/firewall', [FirewallController::class, 'store'])->name('firewall.store');
    Route::post('/firewall/toggle-global', [FirewallController::class, 'toggleGlobal'])->name('firewall.toggle-global');
    Route::delete('/firewall/{rule}', [FirewallController::class, 'destroy'])->name('firewall.destroy');
    Route::post('/firewall/{rule}/toggle', [FirewallController::class, 'toggle'])->name('firewall.toggle');
});

require __DIR__.'/auth.php';
