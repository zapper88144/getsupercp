<?php

namespace App\Mcp\Tools;

use App\Models\Backup;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class RestoreBackup extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Restore a backup in SuperCP.
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

        if ($backup->status !== 'completed') {
            return Response::text("Cannot restore a backup that is in status: {$backup->status}.");
        }

        try {
            $daemon = app(RustDaemonClient::class);

            if ($backup->type === 'web') {
                $domain = WebDomain::where('domain', $backup->source)->first();
                $targetPath = $domain?->root_path ?? "/home/super/web/{$backup->source}/public";

                $daemon->call('restore_backup', [
                    'path' => $backup->path,
                    'target_path' => $targetPath,
                ]);
            } else {
                $daemon->call('restore_db_backup', [
                    'path' => $backup->path,
                    'db_name' => $backup->source,
                ]);
            }

            return Response::text("Restore of backup {$backup->name} completed successfully.");
        } catch (\Exception $e) {
            return Response::text("Restore failed: {$e->getMessage()}");
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
            'backup_id' => $schema->integer('The ID of the backup to restore.'),
        ];
    }
}
