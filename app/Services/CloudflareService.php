<?php

namespace App\Services;

use App\Models\DnsRecord;
use App\Models\DnsZone;
use Exception;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CloudflareService
{
    protected string $baseUrl = 'https://api.cloudflare.com/client/v4';

    protected ?string $apiToken;

    public function __construct()
    {
        $this->apiToken = config('services.cloudflare.api_token');
    }

    protected function request(): PendingRequest
    {
        return Http::withToken($this->apiToken)
            ->baseUrl($this->baseUrl)
            ->acceptJson();
    }

    public function getZoneId(string $domain): ?string
    {
        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->request()->get('/zones', [
                'name' => $domain,
            ]);

            if ($response->successful() && ! empty($response->json('result'))) {
                return $response->json('result.0.id');
            }
        } catch (Exception $e) {
            Log::error("Cloudflare getZoneId error for {$domain}: ".$e->getMessage());
        }

        return null;
    }

    public function syncDnsRecords(DnsZone $zone): bool
    {
        if (! $zone->cloudflare_zone_id) {
            $zoneId = $this->getZoneId($zone->domain);
            if (! $zoneId) {
                Log::warning("Cloudflare Zone ID not found for {$zone->domain}");

                return false;
            }
            $zone->update(['cloudflare_zone_id' => $zoneId]);
        }

        try {
            // Get existing records from Cloudflare to avoid duplicates or to update
            /** @var \Illuminate\Http\Client\Response $cfRecordsResponse */
            $cfRecordsResponse = $this->request()->get("/zones/{$zone->cloudflare_zone_id}/dns_records");
            $cfRecords = $cfRecordsResponse->successful() ? collect($cfRecordsResponse->json('result')) : collect();

            foreach ($zone->dnsRecords as $record) {
                $this->syncRecord($zone->cloudflare_zone_id, $record, $cfRecords);
            }

            $zone->update(['cloudflare_last_sync_at' => now()]);

            return true;
        } catch (Exception $e) {
            Log::error("Cloudflare syncDnsRecords error for {$zone->domain}: ".$e->getMessage());

            return false;
        }
    }

    protected function syncRecord(string $zoneId, DnsRecord $record, $cfRecords): void
    {
        $existing = $cfRecords->first(function ($cf) use ($record) {
            return $cf['name'] === $record->name && $cf['type'] === $record->type;
        });

        $data = [
            'type' => $record->type,
            'name' => $record->name,
            'content' => $record->value,
            'ttl' => $record->ttl ?? 3600,
            'priority' => $record->priority,
            'proxied' => config('services.cloudflare.proxy_default', false),
        ];

        if ($existing) {
            $this->request()->put("/zones/{$zoneId}/dns_records/{$existing['id']}", $data);
        } else {
            $this->request()->post("/zones/{$zoneId}/dns_records", $data);
        }
    }

    public function purgeCache(DnsZone $zone): bool
    {
        if (! $zone->cloudflare_zone_id) {
            return false;
        }

        try {
            /** @var \Illuminate\Http\Client\Response $response */
            $response = $this->request()->post("/zones/{$zone->cloudflare_zone_id}/purge_cache", [
                'purge_everything' => true,
            ]);

            return $response->successful();
        } catch (Exception $e) {
            Log::error("Cloudflare purgeCache error for {$zone->domain}: ".$e->getMessage());

            return false;
        }
    }
}
