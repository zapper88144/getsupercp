<?php

namespace App\Mcp\Tools;

use App\Models\Backup;
use App\Models\Database;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateBackup extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new backup (web domain or database) in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $backupType = $request->input('type');
        $source = $request->input('source');

        if (! $backupType || ! $source) {
            return Response::text('Error: type and source parameters are required.');
        }

        if (! in_array($backupType, ['web', 'database'])) {
            return Response::text('Error: type must be either "web" or "database".');
        }

        // Verify source exists
        if ($backupType === 'web') {
            if (! WebDomain::where('domain', $source)->exists()) {
                return Response::text("Web domain {$source} not found in SuperCP.");
            }
        } else {
            if (! Database::where('name', $source)->exists()) {
                return Response::text("Database {$source} not found in SuperCP.");
            }
        }

        $timestamp = now()->format('Y-m-d_H-i-s');
        $name = "backup_{$backupType}_{$source}_{$timestamp}";
        $path = "/var/lib/supercp/backups/{$name}.tar.gz";

        $backup = Backup::create([
            'name' => $name,
            'type' => $backupType,
            'source' => $source,
            'path' => $path,
            'status' => 'pending',
        ]);

        try {
            $daemon = app(RustDaemonClient::class);

            if ($backupType === 'web') {
                $domain = WebDomain::where('domain', $source)->first();
                $sourcePath = $domain?->root_path ?? "/home/super/web/{$source}/public";

                $response = $daemon->call('create_backup', [
                    'name' => $name,
                    'source_path' => $sourcePath,
                ]);
            } else {
                $response = $daemon->call('create_db_backup', [
                    'db_name' => $source,
                ]);
            }

            if (isset($response['result'])) {
                $actualPath = $response['result'];
                $size = 0;
                if (file_exists($actualPath)) {
                    $size = filesize($actualPath);
                }

                $backup->update([
                    'status' => 'completed',
                    'size' => $size,
                    'path' => $actualPath,
                ]);

                return Response::text('Backup created successfully.');
            } else {
                $backup->update(['status' => 'failed']);

                return Response::text('Backup failed: Daemon did not return a result.');
            }
        } catch (\Exception $e) {
            $backup->update(['status' => 'failed']);

            return Response::text("Backup failed: {$e->getMessage()}");
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
            'type' => $schema->string('The type of backup: "web" or "database".'),
            'source' => $schema->string('The source to backup (domain name for web, database name for database).'),
        ];
    }
}
