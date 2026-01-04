<?php

namespace App\Services;

use App\Models\IpWhitelist;
use Illuminate\Support\Facades\Log;

class IpWhitelistService
{
    /**
     * Add IP to whitelist
     */
    public function addIp(
        string $ipAddress,
        string $reason = 'general',
        ?string $description = null,
        ?int $userId = null,
        bool $isPermanent = true,
        ?int $expiresInHours = null,
    ): IpWhitelist {
        $whitelist = IpWhitelist::create([
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'description' => $description,
            'user_id' => $userId,
            'is_permanent' => $isPermanent,
            'expires_at' => $expiresInHours ? now()->addHours($expiresInHours) : null,
        ]);

        Log::info('IP added to whitelist', [
            'ip_address' => $ipAddress,
            'reason' => $reason,
            'permanent' => $isPermanent,
        ]);

        return $whitelist;
    }

    /**
     * Add IP range (CIDR) to whitelist
     */
    public function addIpRange(
        string $cidr,
        string $reason = 'general',
        ?string $description = null,
        ?int $userId = null,
    ): IpWhitelist {
        $whitelist = IpWhitelist::create([
            'ip_range' => $cidr,
            'reason' => $reason,
            'description' => $description,
            'user_id' => $userId,
            'is_permanent' => true,
        ]);

        Log::info('IP range added to whitelist', [
            'cidr' => $cidr,
            'reason' => $reason,
        ]);

        return $whitelist;
    }

    /**
     * Remove IP from whitelist
     */
    public function removeIp(string $ipAddress): bool
    {
        $deleted = IpWhitelist::where('ip_address', $ipAddress)->delete();

        if ($deleted) {
            Log::info('IP removed from whitelist', ['ip_address' => $ipAddress]);
        }

        return (bool) $deleted;
    }

    /**
     * Check if IP is whitelisted
     */
    public function isWhitelisted(string $ipAddress): bool
    {
        // Check permanent whitelist
        if (IpWhitelist::where('ip_address', $ipAddress)->exists()) {
            return true;
        }

        // Check temporary whitelist (not expired)
        if (IpWhitelist::where('ip_address', $ipAddress)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists()) {
            return true;
        }

        return false;
    }

    /**
     * Get all whitelisted IPs
     */
    public function getWhitelist(): array
    {
        return IpWhitelist::orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Get whitelisted IPs by reason
     */
    public function getByReason(string $reason): array
    {
        return IpWhitelist::where('reason', $reason)
            ->orderByDesc('created_at')
            ->get()
            ->toArray();
    }

    /**
     * Clear expired entries
     */
    public function clearExpired(): int
    {
        $cleared = IpWhitelist::where('expires_at', '<', now())
            ->where('is_permanent', false)
            ->delete();

        if ($cleared > 0) {
            Log::info('Cleared expired whitelist entries', ['count' => $cleared]);
        }

        return $cleared;
    }

    /**
     * Add Cloudflare IPs to whitelist
     */
    public function addCloudflareIps(): array
    {
        // Cloudflare IP ranges (as of 2024)
        $cloudflareIps = [
            '173.245.48.0/20',
            '103.21.244.0/22',
            '103.22.200.0/22',
            '103.31.4.0/22',
            '141.101.64.0/18',
            '108.162.192.0/18',
            '190.93.240.0/20',
            '188.114.96.0/20',
            '197.234.240.0/22',
            '198.41.128.0/17',
            '162.158.0.0/15',
            '104.16.0.0/12',
            '172.64.0.0/13',
            '131.0.72.0/22',
        ];

        $added = [];

        foreach ($cloudflareIps as $cidr) {
            // Remove existing if present
            IpWhitelist::where('ip_range', $cidr)->delete();

            $whitelist = IpWhitelist::create([
                'ip_range' => $cidr,
                'reason' => 'cloudflare',
                'description' => 'Cloudflare CDN IP range',
                'is_permanent' => true,
            ]);

            $added[] = $whitelist;
        }

        Log::info('Added Cloudflare IPs to whitelist', ['count' => count($added)]);

        return $added;
    }

    /**
     * Update whitelist entry
     */
    public function update(int $id, array $data): IpWhitelist
    {
        $whitelist = IpWhitelist::findOrFail($id);
        $whitelist->update($data);

        Log::info('Whitelist entry updated', ['id' => $id]);

        return $whitelist;
    }
}
