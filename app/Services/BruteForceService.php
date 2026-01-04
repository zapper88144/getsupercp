<?php

namespace App\Services;

use App\Models\BruteForceAttempt;
use App\Models\IpWhitelist;
use Illuminate\Support\Facades\Log;

class BruteForceService
{
    public function __construct(
        private SecurityPolicyService $securityPolicyService,
    ) {}

    /**
     * Record a failed login attempt
     */
    public function recordAttempt(string $ipAddress, string $service, ?string $username = null): BruteForceAttempt
    {
        $attempt = BruteForceAttempt::firstOrCreate(
            ['ip_address' => $ipAddress, 'service' => $service],
            [
                'attempt_count' => 1,
                'first_attempt_at' => now(),
                'last_attempt_at' => now(),
                'is_blocked' => false,
                'username' => $username,
            ]
        );

        if ($attempt->wasRecentlyCreated) {
            Log::warning('Failed login attempt recorded', [
                'ip_address' => $ipAddress,
                'service' => $service,
                'username' => $username,
            ]);
        } else {
            $attempt->increment('attempt_count');
            $attempt->update(['last_attempt_at' => now()]);

            // Check if threshold exceeded
            $policy = $this->securityPolicyService->getActivePolicy();
            if ($policy && $attempt->attempt_count >= $policy->failed_login_threshold) {
                $this->blockIp($ipAddress, $service, 'Exceeded failed login threshold');
            }
        }

        return $attempt;
    }

    /**
     * Block an IP address
     */
    public function blockIp(string $ipAddress, string $service, ?string $reason = null): BruteForceAttempt
    {
        $policy = $this->securityPolicyService->getActivePolicy();
        $lockoutDuration = $policy ? $policy->lockout_duration_minutes : 15;

        $attempt = BruteForceAttempt::where('ip_address', $ipAddress)
            ->where('service', $service)
            ->first() ?? BruteForceAttempt::create([
                'ip_address' => $ipAddress,
                'service' => $service,
                'attempt_count' => 0,
                'first_attempt_at' => now(),
                'last_attempt_at' => now(),
                'is_blocked' => true,
                'blocked_until' => now()->addMinutes($lockoutDuration),
                'reason' => $reason,
            ]);

        $attempt->update([
            'is_blocked' => true,
            'blocked_until' => now()->addMinutes($lockoutDuration),
            'reason' => $reason,
        ]);

        Log::warning('IP address blocked', [
            'ip_address' => $ipAddress,
            'service' => $service,
            'reason' => $reason,
        ]);

        return $attempt;
    }

    /**
     * Unblock an IP address
     */
    public function unblockIp(string $ipAddress, string $service): BruteForceAttempt
    {
        $attempt = BruteForceAttempt::where('ip_address', $ipAddress)
            ->where('service', $service)
            ->first();

        if ($attempt) {
            $attempt->update([
                'is_blocked' => false,
                'blocked_until' => null,
                'attempt_count' => 0,
            ]);

            Log::info('IP address unblocked', [
                'ip_address' => $ipAddress,
                'service' => $service,
            ]);
        }

        return $attempt;
    }

    /**
     * Check if an IP is currently blocked
     */
    public function isIpBlocked(string $ipAddress, string $service): bool
    {
        // Check if IP is whitelisted
        if ($this->isIpWhitelisted($ipAddress)) {
            return false;
        }

        $policy = $this->securityPolicyService->getActivePolicy();
        if (! $policy || ! $policy->enable_brute_force_protection) {
            return false;
        }

        $attempt = BruteForceAttempt::where('ip_address', $ipAddress)
            ->where('service', $service)
            ->first();

        if (! $attempt) {
            return false;
        }

        // Check if blocked and lockout period not expired
        if ($attempt->is_blocked && $attempt->blocked_until && $attempt->blocked_until->isFuture()) {
            return true;
        }

        // Auto-unblock if lockout period expired
        if ($attempt->is_blocked && $attempt->blocked_until && $attempt->blocked_until->isPast()) {
            $this->unblockIp($ipAddress, $service);
        }

        return false;
    }

    /**
     * Check if IP is whitelisted
     */
    public function isIpWhitelisted(string $ipAddress): bool
    {
        return IpWhitelist::where('ip_address', $ipAddress)->exists() ||
            IpWhitelist::where('ip_address', $ipAddress)
                ->where('expires_at', '>', now())
                ->exists();
    }

    /**
     * Get brute force attempts summary
     */
    public function getAttemptsSummary(): array
    {
        $activeBlocks = BruteForceAttempt::where('is_blocked', true)
            ->where('blocked_until', '>', now())
            ->count();

        $recentAttempts = BruteForceAttempt::where('last_attempt_at', '>', now()->subHours(1))
            ->count();

        $topAttackers = BruteForceAttempt::orderByDesc('attempt_count')
            ->limit(10)
            ->get(['ip_address', 'service', 'attempt_count', 'last_attempt_at']);

        return [
            'active_blocks' => $activeBlocks,
            'recent_attempts' => $recentAttempts,
            'top_attackers' => $topAttackers,
        ];
    }

    /**
     * Clear expired blocks
     */
    public function clearExpiredBlocks(): int
    {
        $cleared = BruteForceAttempt::where('is_blocked', true)
            ->where('blocked_until', '<', now())
            ->update(['is_blocked' => false, 'blocked_until' => null]);

        if ($cleared > 0) {
            Log::info('Cleared expired brute force blocks', ['count' => $cleared]);
        }

        return $cleared;
    }

    /**
     * Get attempts for an IP
     */
    public function getAttemptsForIp(string $ipAddress): array
    {
        return BruteForceAttempt::where('ip_address', $ipAddress)
            ->orderByDesc('last_attempt_at')
            ->get()
            ->toArray();
    }

    /**
     * Get attempts by service
     */
    public function getAttemptsByService(string $service): array
    {
        return BruteForceAttempt::where('service', $service)
            ->where('is_blocked', true)
            ->where('blocked_until', '>', now())
            ->orderByDesc('attempt_count')
            ->get()
            ->toArray();
    }
}
