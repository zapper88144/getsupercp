<?php

use App\Jobs\UpdateResourceUsage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule resource usage updates to run hourly
Schedule::job(new UpdateResourceUsage)
    ->hourly()
    ->name('update-resource-usage')
    ->withoutOverlapping();

// Schedule SSL certificate renewal to run daily at 2 AM
Schedule::command('app:renew-ssl-certificates')
    ->daily()
    ->at('02:00')
    ->name('ssl-certificate-renewal')
    ->withoutOverlapping()
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::error('SSL certificate renewal failed');
    })
    ->onSuccess(function () {
        \Illuminate\Support\Facades\Log::info('SSL certificate renewal completed successfully');
    });

// Schedule backup schedules to run every minute
Schedule::command('backups:process-schedules')
    ->everyMinute()
    ->name('process-backup-schedules')
    ->withoutOverlapping();
