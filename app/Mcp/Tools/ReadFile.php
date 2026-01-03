<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ReadFile extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Read the contents of a file on the server.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $request->input('path');

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('read_file', ['path' => $path]);

            if (isset($response['error'])) {
                return Response::text('Error reading file: '.$response['error']['message']);
            }

            return Response::text($response['result'] ?? '');
        } catch (\Exception $e) {
            return Response::text("Failed to read file: {$e->getMessage()}");
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
            'path' => $schema->string('The absolute path to the file to read.'),
        ];
    }
}
