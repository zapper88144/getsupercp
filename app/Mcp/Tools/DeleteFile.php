<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteFile extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a file or directory.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'path' => 'required|string',
        ]);

        $path = $validated['path'];

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('delete_file', ['path' => $path]);

            if (isset($response['error'])) {
                return Response::text('Error deleting file: '.$response['error']['message']);
            }

            return Response::text("File or directory at {$path} deleted successfully.");
        } catch (\Exception $e) {
            return Response::text("Failed to delete file: {$e->getMessage()}");
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
            'path' => $schema->string('The absolute path to the file or directory.'),
        ];
    }
}
