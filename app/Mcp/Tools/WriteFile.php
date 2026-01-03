<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class WriteFile extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Write content to a file (creates or overwrites).
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $request->input('path');
        $content = $request->input('content');

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('write_file', [
                'path' => $path,
                'content' => $content,
            ]);

            if (isset($response['error'])) {
                return Response::text('Error writing file: '.$response['error']['message']);
            }

            return Response::text("File written successfully to {$path}.");
        } catch (\Exception $e) {
            return Response::text("Failed to write file: {$e->getMessage()}");
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
            'path' => $schema->string('The absolute path to the file.'),
            'content' => $schema->string('The content to write.'),
        ];
    }
}
