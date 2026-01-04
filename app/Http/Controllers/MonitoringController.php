<?php

namespace App\Http\Controllers;

use App\Services\MonitoringService;
use App\Traits\HandlesDaemonErrors;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected MonitoringService $monitoringService
    ) {}

    public function index(): Response
    {
        try {
            $stats = $this->monitoringService->getSystemStats();

            return Inertia::render('Monitoring/Index', [
                'stats' => $stats,
            ]);
        } catch (\Throwable $e) {
            // For monitoring, we might want to still render the page but with empty stats
            // and a warning message.
            return Inertia::render('Monitoring/Index', [
                'stats' => [],
                'error' => 'Failed to retrieve system stats: '.$e->getMessage(),
            ]);
        }
    }

    public function stats(): array
    {
        try {
            return $this->monitoringService->getSystemStats();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
