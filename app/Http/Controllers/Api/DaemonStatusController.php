<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RustDaemonClient;
use Illuminate\Http\JsonResponse;

class DaemonStatusController extends Controller
{
    public function __construct(private RustDaemonClient $daemon) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'status' => $this->daemon->isRunning() ? 'running' : 'stopped',
            'running' => $this->daemon->isRunning(),
        ]);
    }

    public function emailStatus(): JsonResponse
    {
        $running = $this->daemon->isRunning();

        return response()->json([
            'status' => $running ? 'running' : 'stopped',
            'running' => $running,
            'service' => 'email',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
