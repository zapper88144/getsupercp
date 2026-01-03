<?php

namespace App\Http\Controllers;

use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function __construct(
        protected RustDaemonClient $client
    ) {}

    public function index(): Response
    {
        return Inertia::render('Logs/Index', [
            'logTypes' => [
                ['id' => 'daemon', 'name' => 'System Daemon'],
                ['id' => 'nginx_access', 'name' => 'Nginx Access'],
                ['id' => 'nginx_error', 'name' => 'Nginx Error'],
                ['id' => 'php_error', 'name' => 'PHP Error'],
            ],
        ]);
    }

    public function fetch(Request $request)
    {
        $request->validate([
            'type' => 'required|string|in:daemon,nginx_access,nginx_error,php_error',
            'lines' => 'integer|min:1|max:1000',
        ]);

        $response = $this->client->call('get_logs', [
            'type' => $request->input('type'),
            'lines' => $request->input('lines', 100),
        ]);

        return response()->json([
            'content' => $response['result'] ?? 'No logs found or error reading logs.'
        ]);
    }
}
