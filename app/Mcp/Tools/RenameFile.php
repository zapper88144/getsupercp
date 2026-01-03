<?php

namespace App\Mcp\Tools;

use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class RenameFile extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Rename or move a file or directory.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $from = $request->input('from');
        $to = $request->input('to');

        $daemon = app(RustDaemonClient::class);

        try {
            $response = $daemon->call('rename_file', [
                'from' => $from,
                'to' => $to,
            ]);

            if (isset($response['error'])) {
                return Response::text('Error renaming file: '.$response['error']['message']);
            }

            return Response::text("File or directory renamed from {$from} to {$to} successfully.");
        } catch (\Exception $e) {
            return Response::text("Failed to rename file: {$e->getMessage()}");
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
            'from' => $schema->string('The current absolute path.'),
            'to' => $schema->string('The new absolute path.'),
        ];
    }
}
