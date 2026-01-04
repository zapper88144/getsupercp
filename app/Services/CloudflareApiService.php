<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareApiService
{
    private string $apiToken;

    private string $accountEmail;

    private string $zoneId;

    private string $baseUrl = 'https://api.cloudflare.com/client/v4';

    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.api_token') ?? '';
        $this->accountEmail = config('services.cloudflare.account_email') ?? '';
        $this->zoneId = config('services.cloudflare.zone_id') ?? '';
    }

    /**
     * Get all DNS records for the zone
     */
    public function getDnsRecords(int $page = 1, int $perPage = 50): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                    'page' => $page,
                    'per_page' => $perPage,
                ])
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare DNS records', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get a specific DNS record
     */
    public function getDnsRecord(string $recordId): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$recordId}")
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare DNS record', ['error' => $e->getMessage(), 'record_id' => $recordId]);
            throw $e;
        }
    }

    /**
     * Create a DNS record
     */
    public function createDnsRecord(string $type, string $name, string $content, int $ttl = 1, bool $proxied = false): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/zones/{$this->zoneId}/dns_records", [
                    'type' => $type,
                    'name' => $name,
                    'content' => $content,
                    'ttl' => $ttl,
                    'proxied' => $proxied,
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Created Cloudflare DNS record', [
                'type' => $type,
                'name' => $name,
                'record_id' => $result['id'] ?? null,
            ]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to create Cloudflare DNS record', ['error' => $e->getMessage(), 'name' => $name]);
            throw $e;
        }
    }

    /**
     * Update a DNS record
     */
    public function updateDnsRecord(string $recordId, string $type, string $name, string $content, int $ttl = 1, bool $proxied = false): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->put("{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$recordId}", [
                    'type' => $type,
                    'name' => $name,
                    'content' => $content,
                    'ttl' => $ttl,
                    'proxied' => $proxied,
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Updated Cloudflare DNS record', ['record_id' => $recordId, 'name' => $name]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to update Cloudflare DNS record', ['error' => $e->getMessage(), 'record_id' => $recordId]);
            throw $e;
        }
    }

    /**
     * Delete a DNS record
     */
    public function deleteDnsRecord(string $recordId): bool
    {
        try {
            Http::withToken($this->apiToken)
                ->delete("{$this->baseUrl}/zones/{$this->zoneId}/dns_records/{$recordId}")
                ->throw();

            Log::info('Deleted Cloudflare DNS record', ['record_id' => $recordId]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete Cloudflare DNS record', ['error' => $e->getMessage(), 'record_id' => $recordId]);
            throw $e;
        }
    }

    /**
     * Get zone cache purge status
     */
    public function getCachePurgeStatus(): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}")
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare cache status', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Purge cache for specific files
     */
    public function purgeCacheByUrls(array $urls): bool
    {
        try {
            Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/zones/{$this->zoneId}/purge_cache", [
                    'files' => $urls,
                ])
                ->throw();

            Log::info('Purged Cloudflare cache for URLs', ['count' => count($urls)]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to purge Cloudflare cache', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Purge entire zone cache
     */
    public function purgeAllCache(): bool
    {
        try {
            Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/zones/{$this->zoneId}/purge_cache", [
                    'purge_everything' => true,
                ])
                ->throw();

            Log::info('Purged all Cloudflare cache');

            return true;
        } catch (Exception $e) {
            Log::error('Failed to purge all Cloudflare cache', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get WAF rules
     */
    public function getWafRules(int $page = 1, int $perPage = 50): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/firewall/rules", [
                    'page' => $page,
                    'per_page' => $perPage,
                ])
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare WAF rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create WAF rule
     */
    public function createWafRule(string $description, array $filter, string $action): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/zones/{$this->zoneId}/firewall/rules", [
                    'description' => $description,
                    'filter' => $filter,
                    'action' => $action,
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Created Cloudflare WAF rule', ['description' => $description, 'rule_id' => $result['id'] ?? null]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to create Cloudflare WAF rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update WAF rule
     */
    public function updateWafRule(string $ruleId, string $description, array $filter, string $action): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->put("{$this->baseUrl}/zones/{$this->zoneId}/firewall/rules/{$ruleId}", [
                    'description' => $description,
                    'filter' => $filter,
                    'action' => $action,
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Updated Cloudflare WAF rule', ['rule_id' => $ruleId]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to update Cloudflare WAF rule', ['error' => $e->getMessage(), 'rule_id' => $ruleId]);
            throw $e;
        }
    }

    /**
     * Delete WAF rule
     */
    public function deleteWafRule(string $ruleId): bool
    {
        try {
            Http::withToken($this->apiToken)
                ->delete("{$this->baseUrl}/zones/{$this->zoneId}/firewall/rules/{$ruleId}")
                ->throw();

            Log::info('Deleted Cloudflare WAF rule', ['rule_id' => $ruleId]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete Cloudflare WAF rule', ['error' => $e->getMessage(), 'rule_id' => $ruleId]);
            throw $e;
        }
    }

    /**
     * Get DDoS protection settings
     */
    public function getDdosSettings(): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/settings/security_level")
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare DDoS settings', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update DDoS protection level
     */
    public function updateDdosLevel(string $level): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->patch("{$this->baseUrl}/zones/{$this->zoneId}/settings/security_level", [
                    'value' => $level,
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Updated Cloudflare DDoS level', ['level' => $level]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to update Cloudflare DDoS level', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get rate limiting rules
     */
    public function getRateLimitRules(int $page = 1, int $perPage = 50): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/rate_limit", [
                    'page' => $page,
                    'per_page' => $perPage,
                ])
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare rate limit rules', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create rate limiting rule
     */
    public function createRateLimitRule(string $description, array $match, int $threshold, int $period, string $action): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/zones/{$this->zoneId}/rate_limit", [
                    'description' => $description,
                    'match' => $match,
                    'threshold' => $threshold,
                    'period' => $period,
                    'action' => ['response' => ['status_code' => 429, 'body' => 'Too many requests']],
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Created Cloudflare rate limit rule', ['description' => $description]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to create rate limit rule', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get SSL/TLS certificate settings
     */
    public function getSslSettings(): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/zones/{$this->zoneId}/settings/ssl")
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare SSL settings', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Update SSL/TLS encryption mode
     */
    public function updateSslMode(string $mode): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->patch("{$this->baseUrl}/zones/{$this->zoneId}/settings/ssl", [
                    'value' => $mode,
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Updated Cloudflare SSL mode', ['mode' => $mode]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to update Cloudflare SSL mode', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get origin certificate
     */
    public function getOriginCertificates(int $page = 1): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->baseUrl}/accounts/".config('services.cloudflare.account_id').'/origin_ca_certificates', [
                    'page' => $page,
                ])
                ->throw();

            return $response->json()['result'] ?? [];
        } catch (Exception $e) {
            Log::error('Failed to get origin certificates', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create origin certificate
     */
    public function createOriginCertificate(array $hostnames, int $requestedValidity = 31536000): array
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->post("{$this->baseUrl}/accounts/".config('services.cloudflare.account_id').'/origin_ca_certificates', [
                    'hostnames' => $hostnames,
                    'requested_validity' => $requestedValidity,
                    'certificate_authority' => 'origin_rsa',
                ])
                ->throw();

            $result = $response->json()['result'] ?? [];

            Log::info('Created origin certificate', ['hostnames' => implode(',', $hostnames)]);

            return $result;
        } catch (Exception $e) {
            Log::error('Failed to create origin certificate', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get Cloudflare IP ranges (for whitelist sync)
     */
    public function getCloudflareIpRanges(): array
    {
        try {
            $response = Http::get('https://api.cloudflare.com/client/v4/ips')
                ->throw();

            $data = $response->json()['result'] ?? [];

            return array_merge($data['ipv4_cidrs'] ?? [], $data['ipv6_cidrs'] ?? []);
        } catch (Exception $e) {
            Log::error('Failed to get Cloudflare IP ranges', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Check if domain is active on Cloudflare
     */
    public function isDomainActive(string $domain): bool
    {
        try {
            $records = $this->getDnsRecords();

            foreach ($records as $record) {
                if ($record['name'] === $domain || $record['name'] === "www.$domain") {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            Log::error('Failed to check domain status', ['error' => $e->getMessage(), 'domain' => $domain]);

            return false;
        }
    }

    /**
     * Toggle proxy status for a DNS record
     */
    public function toggleDnsProxy(string $recordId, bool $proxied): array
    {
        try {
            $record = $this->getDnsRecord($recordId);

            return $this->updateDnsRecord(
                $recordId,
                $record['type'],
                $record['name'],
                $record['content'],
                $record['ttl'] ?? 1,
                $proxied
            );
        } catch (Exception $e) {
            Log::error('Failed to toggle DNS proxy', ['error' => $e->getMessage(), 'record_id' => $recordId]);
            throw $e;
        }
    }
}
