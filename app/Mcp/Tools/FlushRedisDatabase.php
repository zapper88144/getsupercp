<?php

namespace App\Mcp\Tools;

use App\Services\RedisService;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class FlushRedisDatabase extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Flush (clear) a Redis database. Use with extreme caution!
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $mode = $request->input('mode', 'database');
        $connection = $request->input('connection', 'default');

        try {
            $service = app(RedisService::class);

            if ($mode === 'all') {
                $service->flushAll();

                return Response::text(json_encode([
                    'flushed' => true,
                    'mode' => 'all',
                    'message' => 'All Redis databases flushed successfully',
                    'warning' => 'This action cleared all data in all Redis databases!',
                ], JSON_PRETTY_PRINT));
            }

            $service->flushDb($connection);

            return Response::text(json_encode([
                'flushed' => true,
                'connection' => $connection,
                'mode' => 'database',
                'message' => "Redis database '{$connection}' flushed successfully",
                'warning' => 'This action cleared all data in the database!',
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text("Error flushing Redis database: {$e->getMessage()}");
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
            'mode' => $schema->string()->enum(['database', 'all'])->description('Flush single database or all databases.')->default('database'),
            'connection' => $schema->string()->description('Redis connection to flush (only used if mode is "database").')->default('default'),
        ];
    }
}
