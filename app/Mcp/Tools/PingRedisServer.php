<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class PingRedisServer extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Test connectivity to a Redis server by sending a ping command.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'connection' => 'string',
        ]);

        $connection = $validated['connection'] ?? 'default';

        try {
            $service = app(RedisService::class);
            $isOnline = $service->ping($connection);

            if ($isOnline) {
                return Response::text(json_encode([
                    'connection' => $connection,
                    'status' => 'online',
                    'message' => "Redis server '{$connection}' is online and responding",
                ], JSON_PRETTY_PRINT));
            }

            return Response::text(json_encode([
                'connection' => $connection,
                'status' => 'offline',
                'message' => "Redis server '{$connection}' is not responding",
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'connection' => $connection,
                'status' => 'error',
                'message' => "Error connecting to Redis: {$e->getMessage()}",
            ], JSON_PRETTY_PRINT));
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
            'connection' => $schema->string()->description('Redis connection to ping (default or cache).')->default('default'),
        ];
    }
}
