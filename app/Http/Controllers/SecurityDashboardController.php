<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BruteForceAttempt;
use App\Models\IpWhitelist;
use App\Models\User;
use App\Services\BruteForceService;
use App\Services\IpWhitelistService;
use App\Services\SecurityPolicyService;
use Inertia\Inertia;
use Inertia\Response;

class SecurityDashboardController extends Controller
{
    public function __construct(
        private SecurityPolicyService $securityPolicyService,
        private BruteForceService $bruteForceService,
        private IpWhitelistService $ipWhitelistService,
    ) {}

    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();

        $policy = $this->securityPolicyService->getActivePolicy();
        $attempts = $this->bruteForceService->getAttemptsSummary();
        $whitelistCount = IpWhitelist::count();

        $metrics = [
            'failed_login_attempts_24h' => AuditLog::whereIn('action', ['login_failed', 'login_lockout', 'rate_limit_auth'])
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'failed_login_attempts_7d' => AuditLog::whereIn('action', ['login_failed', 'login_lockout', 'rate_limit_auth'])
                ->where('created_at', '>=', now()->subDays(7))
                ->count(),
            'active_sessions' => \Illuminate\Support\Facades\DB::table('sessions')->count(),
            'two_fa_enabled_users' => \App\Models\TwoFactorAuthentication::where('is_enabled', true)->count(),
            'total_users' => User::count(),
            'suspicious_ips' => AuditLog::whereIn('action', ['login_failed', 'login_lockout', 'rate_limit_auth', 'rate_limit_api'])
                ->where('created_at', '>=', now()->subDay())
                ->distinct('ip_address')
                ->count(),
            'failed_api_requests_24h' => AuditLog::where('action', 'rate_limit_api')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            'last_security_audit' => AuditLog::latest()->first()?->created_at?->toIso8601String() ?? now()->toIso8601String(),
        ];

        return Inertia::render('Security/Dashboard', [
            'metrics' => $metrics,
            'recentLogs' => AuditLog::with('user')->latest()->limit(5)->get(),
            'failedLogins' => AuditLog::where('result', 'failed')->count(),
            'suspiciousActivity' => AuditLog::where('result', 'failed')->where('created_at', '>=', now()->subHour())->count() > 5,
            'policy' => $policy,
            'attempts' => $attempts,
            'whitelistCount' => $whitelistCount,
            'securitySummary' => $this->securityPolicyService->getPolicySummary(),
        ]);
    }

    public function auditLogs(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();

        // For now, we'll return the last 500 logs to avoid overloading the frontend
        // while still providing enough data for client-side filtering.
        $logs = AuditLog::with('user')
            ->latest()
            ->limit(500)
            ->get()
            ->map(function ($log) {
                return [
                    'id' => $log->id,
                    'user_id' => $log->user_id,
                    'user_name' => $log->user?->name ?? 'System',
                    'action' => $log->action,
                    'description' => $log->description,
                    'ip_address' => $log->ip_address,
                    'result' => $log->result,
                    'created_at' => $log->created_at->toIso8601String(),
                ];
            });

        return Inertia::render('Security/AuditLogs', [
            'logs' => $logs,
        ]);
    }

    /**
     * Get policy details (API endpoint)
     */
    public function getPolicy()
    {
        $policy = $this->securityPolicyService->getActivePolicy();

        return response()->json([
            'policy' => $policy,
            'summary' => $this->securityPolicyService->getPolicySummary(),
        ]);
    }

    /**
     * Update security policy
     */
    public function updatePolicy()
    {
        $validated = request()->validate([
            'enable_firewall' => 'boolean',
            'enable_brute_force_protection' => 'boolean',
            'failed_login_threshold' => 'integer|min:1|max:100',
            'lockout_duration_minutes' => 'integer|min:1|max:1440',
            'enable_ip_filtering' => 'boolean',
            'enable_ssl_enforcement' => 'boolean',
            'enable_cloudflare_security' => 'boolean',
        ]);

        $policy = $this->securityPolicyService->updatePolicy($validated);

        return response()->json([
            'message' => 'Security policy updated successfully',
            'policy' => $policy,
        ]);
    }

    /**
     * Toggle firewall
     */
    public function toggleFirewall()
    {
        $enabled = request()->boolean('enabled');
        $policy = $this->securityPolicyService->toggleFirewall($enabled);

        return response()->json([
            'message' => 'Firewall '.($enabled ? 'enabled' : 'disabled'),
            'policy' => $policy,
        ]);
    }

    /**
     * Toggle brute force protection
     */
    public function toggleBruteForce()
    {
        $enabled = request()->boolean('enabled');
        $policy = $this->securityPolicyService->toggleBruteForceProtection($enabled);

        return response()->json([
            'message' => 'Brute force protection '.($enabled ? 'enabled' : 'disabled'),
            'policy' => $policy,
        ]);
    }

    /**
     * Toggle SSL enforcement
     */
    public function toggleSslEnforcement()
    {
        $enabled = request()->boolean('enabled');
        $policy = $this->securityPolicyService->toggleSslEnforcement($enabled);

        return response()->json([
            'message' => 'SSL enforcement '.($enabled ? 'enabled' : 'disabled'),
            'policy' => $policy,
        ]);
    }

    /**
     * Get brute force metrics
     */
    public function getBruteForceMetrics()
    {
        $summary = $this->bruteForceService->getAttemptsSummary();
        $recentAttempts = BruteForceAttempt::recent(24)->count();
        $totalAttempts = BruteForceAttempt::count();

        return response()->json([
            'summary' => $summary,
            'recentAttempts' => $recentAttempts,
            'totalAttempts' => $totalAttempts,
        ]);
    }

    /**
     * List all brute force attempts
     */
    public function listAttempts()
    {
        $attempts = BruteForceAttempt::orderByDesc('last_attempt_at')
            ->paginate(20);

        return response()->json($attempts);
    }

    /**
     * Get attempts for a specific IP
     */
    public function getIpAttempts($ip)
    {
        $attempts = $this->bruteForceService->getAttemptsForIp($ip);

        return response()->json(['attempts' => $attempts]);
    }

    /**
     * Block an IP address
     */
    public function blockIp()
    {
        $validated = request()->validate([
            'ip_address' => 'required|ip',
            'service' => 'required|string',
            'reason' => 'nullable|string',
        ]);

        $attempt = $this->bruteForceService->blockIp(
            $validated['ip_address'],
            $validated['service'],
            $validated['reason'] ?? 'Manual block by admin'
        );

        return response()->json([
            'message' => 'IP address blocked successfully',
            'attempt' => $attempt,
        ]);
    }

    /**
     * Unblock an IP address
     */
    public function unblockIp()
    {
        $validated = request()->validate([
            'ip_address' => 'required|ip',
            'service' => 'required|string',
        ]);

        $attempt = $this->bruteForceService->unblockIp(
            $validated['ip_address'],
            $validated['service']
        );

        return response()->json([
            'message' => 'IP address unblocked successfully',
            'attempt' => $attempt,
        ]);
    }

    /**
     * Get whitelist
     */
    public function getWhitelist()
    {
        $whitelist = IpWhitelist::orderByDesc('created_at')
            ->paginate(20);

        return response()->json($whitelist);
    }

    /**
     * Get whitelist by reason
     */
    public function getWhitelistByReason($reason)
    {
        $whitelist = $this->ipWhitelistService->getByReason($reason);

        return response()->json(['whitelist' => $whitelist]);
    }

    /**
     * Add IP to whitelist
     */
    public function addToWhitelist()
    {
        $validated = request()->validate([
            'ip_address' => 'required|ip',
            'reason' => 'required|string',
            'description' => 'nullable|string',
            'is_permanent' => 'boolean',
            'expires_in_hours' => 'nullable|integer|min:1',
        ]);

        /** @var User $user */
        $user = auth()->guard('web')->user();

        $whitelist = $this->ipWhitelistService->addIp(
            $validated['ip_address'],
            $validated['reason'],
            $validated['description'] ?? null,
            $user->id,
            $validated['is_permanent'] ?? true,
            $validated['expires_in_hours'] ?? null
        );

        return response()->json([
            'message' => 'IP added to whitelist successfully',
            'whitelist' => $whitelist,
        ]);
    }

    /**
     * Remove IP from whitelist
     */
    public function removeFromWhitelist($id)
    {
        $whitelist = IpWhitelist::findOrFail($id);
        $whitelist->delete();

        return response()->json([
            'message' => 'IP removed from whitelist successfully',
        ]);
    }

    /**
     * Add Cloudflare IPs to whitelist
     */
    public function syncCloudflareIps()
    {
        $added = $this->ipWhitelistService->addCloudflareIps();

        return response()->json([
            'message' => 'Cloudflare IPs synced successfully',
            'count' => count($added),
        ]);
    }

    /**
     * Clear expired brute force blocks
     */
    public function clearExpiredBlocks()
    {
        $cleared = $this->bruteForceService->clearExpiredBlocks();

        return response()->json([
            'message' => "Cleared $cleared expired blocks",
            'cleared' => $cleared,
        ]);
    }

    /**
     * Get security headers
     */
    public function getSecurityHeaders()
    {
        $policy = $this->securityPolicyService->getActivePolicy();

        return response()->json([
            'headers' => $policy ? $policy->security_headers : [],
        ]);
    }

    /**
     * Update security headers
     */
    public function updateSecurityHeaders()
    {
        $validated = request()->validate([
            'headers' => 'required|array',
        ]);

        $policy = $this->securityPolicyService->updateSecurityHeaders($validated['headers']);

        return response()->json([
            'message' => 'Security headers updated successfully',
            'headers' => $policy->security_headers,
        ]);
    }

    /**
     * Get dashboard overview
     */
    public function overview()
    {
        $policy = $this->securityPolicyService->getActivePolicy();
        $attempts = $this->bruteForceService->getAttemptsSummary();
        $whitelistCount = IpWhitelist::count();
        $activeBlocks = BruteForceAttempt::activeBlocks()->count();

        return response()->json([
            'policy' => $policy,
            'attempts' => $attempts,
            'whitelistCount' => $whitelistCount,
            'activeBlocks' => $activeBlocks,
            'summary' => $this->securityPolicyService->getPolicySummary(),
        ]);
    }
}
