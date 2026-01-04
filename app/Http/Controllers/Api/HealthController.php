<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RustDaemonClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    /**
     * Get system health status
     */
    public function index(): JsonResponse
    {
        $status = Cache::remember('system_health', 10, function () {
            $daemonRunning = $this->daemon->isRunning();
            $stats = $daemonRunning ? $this->daemon->getSystemStats() : null;

            return [
                'status' => $daemonRunning ? 'healthy' : 'unhealthy',
                'daemon' => [
                    'running' => $daemonRunning,
                    'socket' => storage_path('framework/sockets/super-daemon.sock'),
                    'exists' => file_exists(storage_path('framework/sockets/super-daemon.sock')),
                ],
                'system' => $stats ? [
                    'load' => $stats['load_avg'] ?? null,
                    'memory' => $stats['memory'] ?? null,
                    'disk' => $stats['disk'] ?? null,
                    'uptime' => $stats['uptime'] ?? null,
                ] : null,
                'timestamp' => now()->toIso8601String(),
            ];
        });

        return response()->json($status, $status['status'] === 'healthy' ? 200 : 503);
    }
}
