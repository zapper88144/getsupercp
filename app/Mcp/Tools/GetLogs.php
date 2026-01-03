<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class GetLogs extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Retrieve system or application logs.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $logType = $request->input('log_type');
        $lines = $request->input('lines', 100);

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('get_logs', [
                'type' => $logType,
                'lines' => $lines,
            ]);

            if (isset($response['error'])) {
                return Response::text('Error retrieving logs: '.$response['error']['message']);
            }

            return Response::text($response['result'] ?? 'No logs found.');
        } catch (\Exception $e) {
            return Response::text("Failed to retrieve logs: {$e->getMessage()}");
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
            'log_type' => $schema->string()->enum(['daemon', 'nginx_access', 'nginx_error', 'php_error'])->description('The type of log to retrieve.'),
            'lines' => $schema->integer()->description('Number of lines to retrieve (max 1000).')->default(100),
        ];
    }
}
