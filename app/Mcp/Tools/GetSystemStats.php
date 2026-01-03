<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetSystemStats extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get real-time system statistics (CPU, Memory, Disk, Uptime) from the SuperCP server.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        try {
            $response = $daemon->call('get_system_stats');
            $stats = $response['result'] ?? [];

            return Response::text(json_encode($stats, JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text('Failed to fetch system stats: '.$e->getMessage());
        }
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
