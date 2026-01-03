<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateDirectory extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new directory.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $path = $request->input('path');

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('create_directory', ['path' => $path]);

            if (isset($response['error'])) {
                return Response::text('Error creating directory: '.$response['error']['message']);
            }

            return Response::text("Directory created successfully at {$path}.");
        } catch (\Exception $e) {
            return Response::text("Failed to create directory: {$e->getMessage()}");
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
            'path' => $schema->string('The absolute path to the new directory.'),
        ];
    }
}
