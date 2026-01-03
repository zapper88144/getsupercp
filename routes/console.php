<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
