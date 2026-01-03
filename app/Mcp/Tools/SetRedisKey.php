<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class SetRedisKey extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Set a key-value pair in Redis with optional TTL (time to live in seconds).
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $key = $request->input('key');
        $value = $request->input('value');
        $ttl = $request->input('ttl');
        $connection = $request->input('connection', 'default');

        try {
            $service = app(RedisService::class);
            $ttlSeconds = $ttl ? (int) $ttl : null;
            $service->set($key, $value, $ttlSeconds, $connection);

            return Response::text(json_encode([
                'connection' => $connection,
                'key' => $key,
                'set' => true,
                'ttl' => $ttlSeconds,
                'message' => "Key '{$key}' set successfully".($ttlSeconds ? " with TTL of {$ttlSeconds}s" : ''),
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text("Error setting Redis key: {$e->getMessage()}");
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
            'key' => $schema->string()->description('The Redis key to set.'),
            'value' => $schema->string()->description('The value to store (can be JSON, plain text, etc.).'),
            'ttl' => $schema->integer()->description('Optional time-to-live in seconds. Omit for no expiration.'),
            'connection' => $schema->string()->description('Redis connection to use (default or cache).')->default('default'),
        ];
    }
}
