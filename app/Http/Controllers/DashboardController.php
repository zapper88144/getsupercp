<?php

namespace App\Http\Controllers;

use App\Models\FirewallRule;
use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\SystemService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(protected SystemService $systemService) {}

    public function index(): Response
    {
        $systemStats = [];
        $serviceStatus = [];
        try {
            $systemStats = $this->systemService->getSystemStats();
            $serviceStatus = $this->systemService->getStatus();
        } catch (\Exception $e) {
            // Log error or handle it
        }

        $counts = [
            'domains' => WebDomain::count(),
            'firewall_rules' => FirewallRule::count(),
            'expiring_ssl' => SslCertificate::where('expires_at', '<=', now()->addDays(30))
                ->where('status', 'active')
                ->count(),
        ];

        $expiringSslDomains = SslCertificate::with('webDomain')
            ->where('expires_at', '<=', now()->addDays(30))
            ->where('status', 'active')
            ->orderBy('expires_at', 'asc')
            ->limit(5)
            ->get();

        if (Auth::user()->is_admin) {
            $counts['users'] = User::count();
            $counts['suspended_users'] = User::where('status', 'suspended')->count();
        }

        return Inertia::render('Dashboard', [
            'stats' => $systemStats,
            'counts' => $counts,
            'expiring_ssl_domains' => $expiringSslDomains,
            'services' => $serviceStatus,
        ]);
    }
}
