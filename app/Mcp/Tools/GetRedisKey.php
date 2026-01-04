<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRedisKey extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieve the value of a specific Redis key and display its type and TTL.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'key' => 'required|string',
            'connection' => 'string',
        ]);

        $key = $validated['key'];
        $connection = $validated['connection'] ?? 'default';

        try {
            $service = app(RedisService::class);

            if (! $service->exists($key, $connection)) {
                return Response::text(json_encode([
                    'connection' => $connection,
                    'key' => $key,
                    'exists' => false,
                ], JSON_PRETTY_PRINT));
            }

            $value = $service->get($key, $connection);
            $details = $service->keyDetails($key, $connection);

            return Response::text(json_encode([
                'connection' => $connection,
                'key' => $key,
                'exists' => true,
                'type' => $details['type'],
                'ttl' => $details['ttl'],
                'memory_bytes' => $details['memory'],
                'value' => $value,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            return Response::text("Error retrieving Redis key: {$e->getMessage()}");
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
            'key' => $schema->string()->description('The Redis key to retrieve.'),
            'connection' => $schema->string()->description('Redis connection to use (default or cache).')->default('default'),
        ];
    }
}
