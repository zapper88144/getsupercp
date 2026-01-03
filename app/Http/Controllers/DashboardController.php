<?php

namespace App\Http\Controllers;

use App\Models\WebDomain;
use App\Models\Database;
use App\Models\FirewallRule;
use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected RustDaemonClient $daemon)
    {
    }

    public function index(): Response
    {
        $systemStats = [];
        try {
            $response = $this->daemon->call('get_system_stats');
            $systemStats = $response['result'] ?? [];
        } catch (\Exception $e) {
            // Log error or handle it
        }

        return Inertia::render('Dashboard', [
            'stats' => $systemStats,
            'counts' => [
                'domains' => WebDomain::count(),
                'firewall_rules' => FirewallRule::count(),
            ]
        ]);
    }
}
