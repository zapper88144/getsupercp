<?php

namespace App\Mcp\Tools;

use App\Models\Backup;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteBackup extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a backup file and its record from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $backupId = $request->input('backup_id');

        if (! $backupId) {
            return Response::text('Error: backup_id parameter is required.');
        }

        $backup = Backup::find($backupId);

        if (! $backup) {
            return Response::text('Backup not found.');
        }

        $name = $backup->name;

        if (file_exists($backup->path)) {
            unlink($backup->path);
        }

        $backup->delete();

        return Response::text("Backup {$name} deleted successfully.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'backup_id' => $schema->integer('The ID of the backup to delete.'),
        ];
    }
}
