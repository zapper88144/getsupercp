<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Centralized caching service for all data access patterns.
 *
 * Provides intelligent cache management with TTL-based invalidation,
 * tagged caching for selective purging, and performance monitoring.
 */
class CacheService
{
    /**
     * Cache key prefixes for different features
     */
    private const PREFIXES = [
        'domains' => 'cache:domains:',
        'ssl' => 'cache:ssl:',
        'backups' => 'cache:backups:',
        'databases' => 'cache:databases:',
        'email' => 'cache:email:',
        'firewall' => 'cache:firewall:',
        'monitoring' => 'cache:monitoring:',
        'users' => 'cache:users:',
        'stats' => 'cache:stats:',
    ];

    /**
     * Default cache TTLs (in minutes)
     */
    private const TTLS = [
        'short' => 5,      // 5 minutes for frequently changing data
        'medium' => 15,    // 15 minutes for moderately changing data
        'long' => 60,      // 1 hour for stable data
        'persistent' => 1440, // 24 hours for rarely changing data
    ];

    /**
     * Get cached user domains with intelligent TTL
     *
     * @param  string  $ttl  Cache duration key
     * @return mixed
     */
    public function getUserDomains(int $userId, string $ttl = 'medium')
    {
        return Cache::tags(['domains', "user:{$userId}"])->rememberForever(
            self::PREFIXES['domains'].$userId,
            fn () => $this->fetchUserDomains($userId)
        );
    }

    /**
     * Get user SSL certificates with caching
     *
     * @return mixed
     */
    public function getUserSslCertificates(int $userId)
    {
        return Cache::tags(['ssl', "user:{$userId}"])->remember(
            self::PREFIXES['ssl'].$userId,
            now()->addMinutes(self::TTLS['long']),
            fn () => $this->fetchUserSslCertificates($userId)
        );
    }

    /**
     * Get backup schedule for user with caching
     *
     * @return mixed
     */
    public function getUserBackupSchedules(int $userId)
    {
        return Cache::tags(['backups', "user:{$userId}"])->remember(
            self::PREFIXES['backups'].$userId,
            now()->addMinutes(self::TTLS['medium']),
            fn () => $this->fetchBackupSchedules($userId)
        );
    }

    /**
     * Get user databases with caching
     *
     * @return mixed
     */
    public function getUserDatabases(int $userId)
    {
        return Cache::tags(['databases', "user:{$userId}"])->remember(
            self::PREFIXES['databases'].$userId,
            now()->addMinutes(self::TTLS['long']),
            fn () => $this->fetchUserDatabases($userId)
        );
    }

    /**
     * Get user email accounts with caching
     *
     * @return mixed
     */
    public function getUserEmailAccounts(int $userId)
    {
        return Cache::tags(['email', "user:{$userId}"])->remember(
            self::PREFIXES['email'].$userId,
            now()->addMinutes(self::TTLS['medium']),
            fn () => $this->fetchUserEmailAccounts($userId)
        );
    }

    /**
     * Get firewall rules for user with caching
     *
     * @return mixed
     */
    public function getUserFirewallRules(int $userId)
    {
        return Cache::tags(['firewall', "user:{$userId}"])->remember(
            self::PREFIXES['firewall'].$userId,
            now()->addMinutes(self::TTLS['short']),
            fn () => $this->fetchFirewallRules($userId)
        );
    }

    /**
     * Get monitoring alerts with caching
     *
     * @return mixed
     */
    public function getUserMonitoringAlerts(int $userId)
    {
        return Cache::tags(['monitoring', "user:{$userId}"])->remember(
            self::PREFIXES['monitoring'].$userId,
            now()->addMinutes(self::TTLS['short']),
            fn () => $this->fetchMonitoringAlerts($userId)
        );
    }

    /**
     * Get user statistics with caching
     *
     * @return mixed
     */
    public function getUserStatistics(int $userId)
    {
        return Cache::tags(['stats', "user:{$userId}"])->remember(
            self::PREFIXES['stats'].$userId,
            now()->addMinutes(self::TTLS['short']),
            fn () => $this->computeUserStatistics($userId)
        );
    }

    /**
     * Invalidate all caches for a specific user
     */
    public function invalidateUserCache(int $userId): bool
    {
        try {
            Cache::tags(["user:{$userId}"])->flush();
            Log::info("Cache invalidated for user {$userId}");

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to invalidate cache for user {$userId}: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Invalidate specific domain cache
     */
    public function invalidateDomainCache(int $domainId, int $userId): bool
    {
        try {
            Cache::tags(['domains', "user:{$userId}"])->flush();
            Cache::forget(self::PREFIXES['domains'].$userId);

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to invalidate domain cache: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Invalidate SSL certificate cache
     */
    public function invalidateSslCache(int $userId): bool
    {
        try {
            Cache::tags(['ssl', "user:{$userId}"])->flush();

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to invalidate SSL cache: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Invalidate backup cache
     */
    public function invalidateBackupCache(int $userId): bool
    {
        try {
            Cache::tags(['backups', "user:{$userId}"])->flush();

            return true;
        } catch (\Exception $e) {
            Log::error("Failed to invalidate backup cache: {$e->getMessage()}");

            return false;
        }
    }

    /**
     * Get cache statistics
     */
    public function getStats(): array
    {
        return [
            'store' => config('cache.default'),
            'prefix' => config('cache.prefix'),
            'ttls' => self::TTLS,
            'prefixes' => self::PREFIXES,
        ];
    }

    /**
     * Fetch user domains (actual query execution)
     *
     * @return mixed
     */
    private function fetchUserDomains(int $userId)
    {
        return \App\Models\WebDomain::where('user_id', $userId)
            ->select(['id', 'user_id', 'domain', 'php_version', 'is_active', 'has_ssl', 'ssl_expires_at'])
            ->get();
    }

    /**
     * Fetch user SSL certificates
     *
     * @return mixed
     */
    private function fetchUserSslCertificates(int $userId)
    {
        return \App\Models\SslCertificate::where('user_id', $userId)
            ->with('webDomain:id,domain')
            ->select(['id', 'user_id', 'web_domain_id', 'issuer', 'expires_at', 'status'])
            ->get();
    }

    /**
     * Fetch backup schedules
     *
     * @return mixed
     */
    private function fetchBackupSchedules(int $userId)
    {
        return \App\Models\BackupSchedule::where('user_id', $userId)
            ->with('database:id,name')
            ->select(['id', 'user_id', 'database_id', 'frequency', 'retention_days', 'is_active'])
            ->get();
    }

    /**
     * Fetch user databases
     *
     * @return mixed
     */
    private function fetchUserDatabases(int $userId)
    {
        return \App\Models\Database::where('user_id', $userId)
            ->select(['id', 'user_id', 'name', 'type', 'created_at'])
            ->get();
    }

    /**
     * Fetch user email accounts
     *
     * @return mixed
     */
    private function fetchUserEmailAccounts(int $userId)
    {
        return \App\Models\EmailAccount::where('user_id', $userId)
            ->select(['id', 'user_id', 'email', 'mailbox', 'quota', 'used_space', 'created_at'])
            ->get();
    }

    /**
     * Fetch firewall rules
     *
     * @return mixed
     */
    private function fetchFirewallRules(int $userId)
    {
        return \App\Models\FirewallRule::where('user_id', $userId)
            ->select(['id', 'user_id', 'protocol', 'port', 'ip_address', 'is_active'])
            ->get();
    }

    /**
     * Fetch monitoring alerts
     *
     * @return mixed
     */
    private function fetchMonitoringAlerts(int $userId)
    {
        return \App\Models\MonitoringAlert::where('user_id', $userId)
            ->select(['id', 'user_id', 'type', 'threshold', 'is_active', 'created_at'])
            ->latest()
            ->limit(50)
            ->get();
    }

    /**
     * Compute user statistics
     */
    private function computeUserStatistics(int $userId): array
    {
        return [
            'total_domains' => \App\Models\WebDomain::where('user_id', $userId)->count(),
            'active_domains' => \App\Models\WebDomain::where('user_id', $userId)->where('is_active', true)->count(),
            'ssl_certificates' => \App\Models\SslCertificate::where('user_id', $userId)->count(),
            'total_databases' => \App\Models\Database::where('user_id', $userId)->count(),
            'email_accounts' => \App\Models\EmailAccount::where('user_id', $userId)->count(),
            'backup_schedules' => \App\Models\BackupSchedule::where('user_id', $userId)->count(),
            'firewall_rules' => \App\Models\FirewallRule::where('user_id', $userId)->count(),
        ];
    }
}
