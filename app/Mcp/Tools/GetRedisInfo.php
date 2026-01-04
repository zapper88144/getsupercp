<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRedisInfo extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get comprehensive Redis server information including memory usage, connected clients, and replication status.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'section' => 'string',
            'connection' => 'string',
        ]);

        $section = $validated['section'] ?? 'default';
        $connection = $validated['connection'] ?? 'default';

        try {
            $service = app(RedisService::class);
            $info = $service->info($section, $connection);

            return Response::text(json_encode([
                'connection' => $connection,
                'section' => $section,
                'info' => $info,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            return Response::text("Error retrieving Redis info: {$e->getMessage()}");
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
            'section' => $schema->string()->description('Info section (default, server, clients, memory, persistence, stats, replication, cpu, cluster, keyspace).')->default('default'),
            'connection' => $schema->string()->description('Redis connection to use (default or cache).')->default('default'),
        ];
    }
}
