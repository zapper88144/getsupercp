<?php

namespace App\Http\Controllers;

use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    public function index(): Response
    {
        $stats = $this->daemon->call('get_system_stats');

        return Inertia::render('Monitoring/Index', [
            'stats' => $stats['result'] ?? [],
        ]);
    }

    public function stats(): array
    {
        $stats = $this->daemon->call('get_system_stats');

        return $stats['result'] ?? [];
    }
}
