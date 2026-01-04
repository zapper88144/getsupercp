<?php

namespace App\Listeners;

use App\Services\BruteForceService;
use Illuminate\Auth\Events\Failed;

class RecordFailedLogin
{
    public function __construct(private BruteForceService $bruteForceService) {}

    public function handle(Failed $event): void
    {
        $this->bruteForceService->recordAttempt(
            ipAddress: request()->ip() ?? '0.0.0.0',
            service: 'http',
            username: $event->credentials['email'] ?? null
        );
    }
}
