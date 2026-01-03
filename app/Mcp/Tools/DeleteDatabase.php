<?php

namespace App\Mcp\Tools;

use App\Models\Database;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteDatabase extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a database from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'database_id' => 'required|integer|exists:databases,id',
        ]);

        $database = Database::findOrFail($validated['database_id']);

        try {
            $daemon->call('delete_database', [
                'name' => $database->name,
                'type' => $database->type,
            ]);
        } catch (\Exception $e) {
            return Response::text('Failed to delete database from system: '.$e->getMessage());
        }

        $database->delete();

        return Response::text("Successfully deleted database: {$database->name}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'database_id' => $schema->integer()->description('The ID of the database to delete.'),
        ];
    }
}
