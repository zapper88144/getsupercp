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
}
