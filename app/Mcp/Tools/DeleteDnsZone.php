<?php

namespace App\Mcp\Tools;

use App\Models\DnsZone;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteDnsZone extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a DNS zone from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'zone_id' => 'required|integer|exists:dns_zones,id',
        ]);

        $zone = DnsZone::findOrFail($validated['zone_id']);

        $domain = $zone->domain;

        try {
            $daemon = app(RustDaemonClient::class);
            $daemon->call('delete_dns_zone', ['domain' => $domain]);
        } catch (\Exception $e) {
            return Response::text("Failed to sync deletion: {$e->getMessage()}");
        }

        $zone->delete();

        return Response::text("DNS zone for {$domain} deleted successfully.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'zone_id' => $schema->integer('The ID of the DNS zone to delete.'),
        ];
    }
}
