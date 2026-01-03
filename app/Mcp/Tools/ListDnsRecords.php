<?php

namespace App\Mcp\Tools;

use App\Models\DnsZone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListDnsRecords extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List all DNS records for a specific zone.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $zoneId = $request->input('zone_id');

        if (! $zoneId) {
            return Response::text('Error: zone_id parameter is required.');
        }

        $zone = DnsZone::with('dnsRecords')->find($zoneId);

        if (! $zone) {
            return Response::text('DNS zone not found.');
        }

        return Response::text(json_encode([
            'zone' => $zone->domain,
            'records' => $zone->dnsRecords->map(fn ($record) => [
                'id' => $record->id,
                'type' => $record->type,
                'name' => $record->name,
                'value' => $record->value,
                'priority' => $record->priority,
                'ttl' => $record->ttl,
            ])->toArray(),
        ], JSON_PRETTY_PRINT));
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
        ];
    }
}
