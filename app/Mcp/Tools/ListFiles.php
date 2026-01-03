<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListFiles extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List files and directories in a specific path on the server.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $request->input('path', '/');

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('list_files', ['path' => $path]);

            if (isset($response['error'])) {
                return Response::text('Error listing files: '.$response['error']['message']);
            }

            return Response::text(json_encode($response['result'] ?? [], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text("Failed to list files: {$e->getMessage()}");
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
            'path' => $schema->string('The absolute path to list (e.g., /home/super/web/example.com/public).'),
        ];
    }
}
