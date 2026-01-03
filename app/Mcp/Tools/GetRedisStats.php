<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetRedisStats extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Get Redis memory statistics and database size information.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $connection = $request->input('connection', 'default');

        try {
            $service = app(RedisService::class);
            $memoryStats = $service->memoryStats($connection);
            $dbSize = $service->dbSize($connection);

            return Response::text(json_encode([
                'connection' => $connection,
                'database_size' => [
                    'keys' => $dbSize,
                    'description' => 'Total number of keys in the database',
                ],
                'memory' => $memoryStats,
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } catch (\Exception $e) {
            return Response::text("Error retrieving Redis stats: {$e->getMessage()}");
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
            'connection' => $schema->string()->description('Redis connection to use (default or cache).')->default('default'),
        ];
    }
}
