<?php

namespace App\Mcp\Tools;

use App\Models\DnsZone;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateDnsRecords extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update DNS records for a zone. This replaces the existing records with the provided list.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|exists:dns_zones,id',
            'records' => 'required|array',
            'records.*.name' => 'required|string',
            'records.*.type' => 'required|string|in:A,AAAA,CNAME,MX,TXT,NS,SRV',
            'records.*.value' => 'required|string',
            'records.*.ttl' => 'nullable|integer',
            'records.*.priority' => 'nullable|integer',
            'records.*.id' => 'nullable|integer',
        ]);

        $zoneId = $validated['zone_id'];
        $records = $validated['records'];

        $zone = DnsZone::findOrFail($zoneId);

        $submittedIds = collect($records)->pluck('id')->filter()->toArray();

        // Delete records not in submitted list
        $zone->dnsRecords()->whereNotIn('id', $submittedIds)->delete();

        foreach ($records as $recordData) {
            if (isset($recordData['id'])) {
                $zone->dnsRecords()->where('id', $recordData['id'])->update(collect($recordData)->except('id')->toArray());
            } else {
                $zone->dnsRecords()->create($recordData);
            }
        }

        // Sync with daemon
        try {
            $daemon = app(RustDaemonClient::class);
            $allRecords = $zone->dnsRecords()->get()->map(function ($record) {
                return [
                    'name' => $record->name,
                    'type' => $record->type,
                    'value' => $record->value,
                    'priority' => $record->priority,
                    'ttl' => $record->ttl,
                ];
            })->toArray();

            $daemon->call('update_dns_zone', [
                'domain' => $zone->domain,
                'records' => $allRecords,
            ]);
        } catch (\Exception $e) {
            return Response::text("Records updated but sync failed: {$e->getMessage()}");
        }

        return Response::text("DNS records for {$zone->domain} updated successfully.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'zone_id' => $schema->integer('The ID of the DNS zone.'),
            'records' => $schema->array(
                'The complete list of DNS records for the zone.'
            ),
        ];
    }
}
