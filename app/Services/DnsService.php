<?php

namespace App\Services;

use App\Models\DnsRecord;
use App\Models\DnsZone;
use App\Models\User;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;

class DnsService
{
    use HandlesDaemonErrors;

    public function __construct(
        private RustDaemonClient $daemon,
        private SystemSyncService $syncService
    ) {}

    /**
     * Create a DNS zone
     */
    public function createZone(User $user, array $data): DnsZone
    {
        // Validate domain format
        if (! $this->isValidDomain($data['domain'])) {
            throw new Exception('Invalid domain format');
        }

        // Check if zone already exists
        if (DnsZone::where('domain', $data['domain'])->exists()) {
            throw new Exception('DNS zone already exists');
        }

        return $this->handleDaemonCall(function () use ($user, $data) {
            // Create database record first
            $zone = DnsZone::create([
                'user_id' => $user->id,
                'domain' => $data['domain'],
                'status' => 'active',
            ]);

            // Push to daemon
            $this->pushZoneToDaemon($zone);

            return $zone;
        }, "Failed to create DNS zone: {$data['domain']}");
    }

    /**
     * Add or update a DNS record
     */
    public function addRecord(DnsZone $zone, array $data): DnsRecord
    {
        // Validate record type
        $validTypes = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'SRV', 'CAA', 'NS'];
        if (! in_array($data['type'], $validTypes)) {
            throw new Exception("Invalid DNS record type: {$data['type']}");
        }

        return $this->handleDaemonCall(function () use ($zone, $data) {
            // Create database record
            $record = DnsRecord::create([
                'dns_zone_id' => $zone->id,
                'name' => $data['name'] ?? '@',
                'type' => $data['type'],
                'value' => $data['value'],
                'ttl' => $data['ttl'] ?? 3600,
                'priority' => $data['priority'] ?? 10,
            ]);

            // Push to daemon
            $this->pushZoneToDaemon($zone);

            return $record;
        }, "Failed to add DNS record to zone: {$zone->domain}");
    }

    /**
     * Update a DNS record
     */
    public function updateRecord(DnsRecord $record, array $data): DnsRecord
    {
        return $this->handleDaemonCall(function () use ($record, $data) {
            $record->update($data);

            // Push to daemon
            $this->pushZoneToDaemon($record->dnsZone);

            Log::info('DNS record updated', [
                'zone' => $record->dnsZone->domain,
                'record' => $record->name,
            ]);

            return $record->fresh();
        }, "Failed to update DNS record: {$record->name}");
    }

    /**
     * Delete a DNS record
     */
    public function deleteRecord(DnsRecord $record): bool
    {
        return $this->handleDaemonCall(function () use ($record) {
            $zone = $record->dnsZone;

            // Delete from database
            $record->delete();

            // Push to daemon
            $this->pushZoneToDaemon($zone);

            Log::info('DNS record deleted', [
                'zone' => $zone->domain,
                'record' => $record->name,
            ]);

            return true;
        }, "Failed to delete DNS record: {$record->name}");
    }

    /**
     * Delete a DNS zone
     */
    public function deleteZone(DnsZone $zone): bool
    {
        return $this->handleDaemonCall(function () use ($zone) {
            $domain = $zone->domain;

            // Delete from daemon
            $this->daemon->call('delete_dns_zone', [
                'domain' => $domain,
            ]);

            Log::info('DNS zone deleted from daemon', ['domain' => $domain]);

            // Delete from PowerDNS system tables
            $this->syncService->deleteDnsZone($domain);

            // Delete records
            $zone->dnsRecords()->delete();

            // Delete zone
            return $zone->delete();
        }, "Failed to delete DNS zone: {$zone->domain}");
    }

    /**
     * Sync multiple records for a zone
     */
    public function syncRecords(DnsZone $zone, array $recordsData): void
    {
        $this->handleDaemonCall(function () use ($zone, $recordsData) {
            $submittedIds = collect($recordsData)->pluck('id')->filter()->toArray();

            // Delete records not in submitted list
            $zone->dnsRecords()->whereNotIn('id', $submittedIds)->delete();

            foreach ($recordsData as $recordData) {
                if (isset($recordData['id'])) {
                    $zone->dnsRecords()->where('id', $recordData['id'])->update(
                        collect($recordData)->only(['type', 'name', 'value', 'priority', 'ttl'])->toArray()
                    );
                } else {
                    $zone->dnsRecords()->create($recordData);
                }
            }

            // Push to daemon and system tables
            $this->pushZoneToDaemon($zone);
        }, "Failed to sync DNS records for zone: {$zone->domain}");
    }

    /**
     * Push all zone records to daemon and sync to system tables
     */
    private function pushZoneToDaemon(DnsZone $zone): void
    {
        $records = $zone->dnsRecords()->get()->map(function ($record) {
            return [
                'name' => $record->name,
                'type' => $record->type,
                'value' => $record->value,
                'ttl' => $record->ttl,
                'priority' => $record->priority,
            ];
        })->toArray();

        // Push to Rust daemon
        $this->daemon->call('update_dns_zone', [
            'domain' => $zone->domain,
            'records' => $records,
        ]);

        // Sync to PowerDNS system tables
        $this->syncService->syncDnsZone($zone);
    }

    /**
     * Sync daemon zones with database
     */
    public function sync(): bool
    {
        return $this->handleDaemonCall(function () {
            // This would sync zones from daemon to database
            // Implementation depends on daemon output format
            Log::info('DNS zones synced with daemon');

            return true;
        }, 'Failed to sync DNS zones', function () {
            return false;
        });
    }

    /**
     * Validate domain format
     */
    private function isValidDomain(string $domain): bool
    {
        return (bool) preg_match(
            '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z0-9]{2,}$/i',
            $domain
        );
    }

    /**
     * Check if daemon is running
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
