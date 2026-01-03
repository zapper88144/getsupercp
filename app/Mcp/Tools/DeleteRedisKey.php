<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteRedisKey extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete one or more keys from Redis by key name or pattern.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $pattern = $request->input('pattern');
        $connection = $request->input('connection', 'default');

        try {
            $service = app(RedisService::class);
            $keys = $service->keys($pattern, $connection);

            if (empty($keys)) {
                return Response::text(json_encode([
                    'connection' => $connection,
                    'pattern' => $pattern,
                    'deleted' => 0,
                    'message' => 'No keys found matching pattern',
                ], JSON_PRETTY_PRINT));
            }

            $deleted = 0;
            foreach ($keys as $key) {
                $deleted += $service->delete($key, $connection);
            }

            return Response::text(json_encode([
                'connection' => $connection,
                'pattern' => $pattern,
                'deleted' => $deleted,
                'message' => "Successfully deleted {$deleted} key(s)",
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text("Error deleting Redis keys: {$e->getMessage()}");
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
            'pattern' => $schema->string()->description('Key pattern to delete (e.g., *, users:*, cache:*). Use * with caution!'),
            'connection' => $schema->string()->description('Redis connection to use (default or cache).')->default('default'),
        ];
    }
}
