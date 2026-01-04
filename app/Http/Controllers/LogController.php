<?php

namespace App\Http\Controllers;

use App\Services\LogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LogController extends Controller
{
    public function __construct(
        protected LogService $logService
    ) {}

    public function index(): Response
    {
        return Inertia::render('Logs/Index', [
            'logTypes' => $this->logService->getLogTypes(),
        ]);
    }

    public function fetch(Request $request): JsonResponse
    {
        $request->validate([
            'type' => 'required|string|in:daemon,nginx_access,nginx_error,php_error',
            'lines' => 'integer|min:1|max:1000',
        ]);

        $content = $this->logService->getLogs(
            $request->input('type'),
            $request->input('lines', 100)
        );

        return response()->json([
            'content' => $content,
        ]);
    }
}
