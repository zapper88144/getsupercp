<?php

namespace App\Http\Controllers;

use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(
        protected RustDaemonClient $client
    ) {}

    public function index(): Response
    {
        return Inertia::render('Services/Index');
    }

    public function status()
    {
        $response = $this->client->call('get_status');

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 500);
        }

        return response()->json($response['result'] ?? []);
    }

    public function restart(Request $request)
    {
        $request->validate([
            'service' => 'required|string',
        ]);

        $response = $this->client->call('restart_service', [
            'service' => $request->input('service'),
        ]);

        if (isset($response['error'])) {
            return response()->json(['message' => $response['error']['message']], 500);
        }

        return response()->json(['message' => $response['result']]);
    }

    public function getLogs(string $service)
    {
        // Map service names to log file paths
        $logMap = [
            'nginx' => '/var/log/supercp/nginx_error.log',
            'php8.4-fpm' => '/var/log/supercp/php_error.log',
            'mysql' => '/var/log/mysql/error.log',
            'redis-server' => '/var/log/redis/redis-server.log',
        ];

        if (! isset($logMap[$service])) {
            return response()->json(['error' => 'Unknown service'], 400);
        }

        $response = $this->client->call('get_service_logs', [
            'service' => $service,
            'lines' => 50,
        ]);

        if (isset($response['error'])) {
            return response()->json(['error' => $response['error']['message']], 500);
        }

        return response()->json(['content' => $response['result'] ?? 'No logs found']);
    }
}
