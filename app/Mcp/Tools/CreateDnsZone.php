<?php

namespace App\Mcp\Tools;

use App\Models\DnsZone;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateDnsZone extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new DNS zone in SuperCP with default records.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $domain = $request->input('domain');

        if (! $domain) {
            return Response::text('Error: domain parameter is required.');
        }

        if (DnsZone::where('domain', $domain)->exists()) {
            return Response::text("DNS zone for {$domain} already exists.");
        }

        $zone = DnsZone::create(['domain' => $domain]);

        // Create default records
        $zone->dnsRecords()->createMany([
            ['type' => 'A', 'name' => '@', 'value' => '127.0.0.1'],
            ['type' => 'A', 'name' => 'www', 'value' => '127.0.0.1'],
            ['type' => 'NS', 'name' => '@', 'value' => 'ns1.supercp.com.'],
            ['type' => 'NS', 'name' => '@', 'value' => 'ns2.supercp.com.'],
        ]);

        try {
            $daemon = app(RustDaemonClient::class);
            $records = $zone->dnsRecords()->get()->map(function ($record) {
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
                'records' => $records,
            ]);
        } catch (\Exception $e) {
            return Response::text("DNS zone created but sync failed: {$e->getMessage()}");
        }

        return Response::text("DNS zone for {$domain} created successfully.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'domain' => $schema->string('The domain name for the DNS zone (e.g., example.com).'),
        ];
    }
}
