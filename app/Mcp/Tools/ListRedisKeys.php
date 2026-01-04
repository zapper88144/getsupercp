<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListRedisKeys extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List all Redis keys matching a pattern and display their types and TTLs.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'pattern' => 'string',
            'connection' => 'string',
            'limit' => 'integer|min:1|max:1000',
        ]);

        $pattern = $validated['pattern'] ?? '*';
        $connection = $validated['connection'] ?? 'default';
        $limit = $validated['limit'] ?? 100;

        try {
            $service = app(RedisService::class);
            $keys = $service->keys($pattern, $connection);
            $limited = array_slice($keys, 0, $limit);

            $keyDetails = [];
            foreach ($limited as $key) {
                $details = $service->keyDetails($key, $connection);
                $keyDetails[] = $details;
            }

            return Response::text(json_encode([
                'connection' => $connection,
                'pattern' => $pattern,
                'total_found' => count($keys),
                'displayed' => count($limited),
                'limit' => $limit,
                'keys' => $keyDetails,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            return Response::text("Error listing Redis keys: {$e->getMessage()}");
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
            'pattern' => $schema->string()->description('Key pattern to search for (e.g., *, users:*, cache:*).')->default('*'),
            'connection' => $schema->string()->description('Redis connection to use (default or cache).')->default('default'),
            'limit' => $schema->integer()->description('Maximum number of keys to return.')->default(100),
        ];
    }
}
