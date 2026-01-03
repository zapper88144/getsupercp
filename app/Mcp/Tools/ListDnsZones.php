<?php

namespace App\Mcp\Tools;

use App\Models\DnsZone;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListDnsZones extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List all DNS zones managed by SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $zones = DnsZone::withCount('dnsRecords')->get();

        if ($zones->isEmpty()) {
            return Response::text('No DNS zones found.');
        }

        return Response::text($zones->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}
