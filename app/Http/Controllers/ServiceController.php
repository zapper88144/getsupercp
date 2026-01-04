<?php

namespace App\Http\Controllers;

use App\Services\SystemService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected SystemService $systemService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Services/Index');
    }

    public function status()
    {
        try {
            $response = $this->systemService->getStatus();

            return response()->json($response);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to get service status.');
        }
    }

    public function restart(Request $request)
    {
        $request->validate([
            'service' => 'required|string',
        ]);

        try {
            $response = $this->systemService->restartService($request->input('service'));

            return response()->json(['message' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to restart service.');
        }
    }

    public function getLogs(string $service)
    {
        try {
            $response = $this->systemService->getServiceLogs($service);

            return response()->json(['content' => $response]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to get service logs.');
        }
    }
}
