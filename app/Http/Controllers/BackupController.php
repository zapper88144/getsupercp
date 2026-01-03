<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Database;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    public function __construct(protected RustDaemonClient $daemon) {}

    public function index(): Response
    {
        return Inertia::render('Backups/Index', [
            'backups' => Backup::latest()->get(),
            'domains' => WebDomain::all(),
            'databases' => Database::all(),
        ]);
    }

    public function download(Backup $backup): BinaryFileResponse
    {
        $this->authorize('view', $backup);

        if (! file_exists($backup->path)) {
            abort(404);
        }

        return response()->download($backup->path);
    }

    public function restore(Backup $backup): RedirectResponse
    {
        $this->authorize('view', $backup);

        try {
            if ($backup->type === 'web') {
                $domain = WebDomain::where('domain', $backup->source)->first();
                $targetPath = $domain ? $domain->root_path : "/home/super/web/{$backup->source}/public";
                
                $this->daemon->call('restore_backup', [
                    'path' => $backup->path,
                    'target_path' => $targetPath,
                ]);
            } else {
                $this->daemon->call('restore_db_backup', [
                    'path' => $backup->path,
                    'db_name' => $backup->source,
                ]);
            }

            return back()->with('success', 'Restore completed successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Restore failed: '.$e->getMessage());
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:web,database',
            'source' => 'required|string',
        ]);

        $type = $request->type;
        $source = $request->source;
        $userId = Auth::id();
        $timestamp = now()->format('Y-m-d_H-i-s');
        $name = "backup_{$type}_{$source}_{$timestamp}";
        $path = "/var/lib/supercp/backups/{$name}.tar.gz"; // Default system path

        $backup = Backup::create([
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'source' => $source,
            'path' => $path,
            'status' => 'pending',
        ]);

        // Trigger the backup via Rust daemon
        try {
            if ($type === 'web') {
                $domain = WebDomain::where('domain', $source)->first();
                $sourcePath = $domain ? $domain->root_path : "/home/super/web/{$source}/public";

                $response = $this->daemon->call('create_backup', [
                    'name' => $name,
                    'source_path' => $sourcePath,
                ]);
            } else {
                // For database, source is the database name
                $response = $this->daemon->call('create_db_backup', [
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
            } else {
                $backup->update(['status' => 'failed']);
            }
        } catch (\Exception $e) {
            $backup->update(['status' => 'failed']);
        }

        return back();
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);

        if (file_exists($backup->path)) {
            unlink($backup->path);
        }
        $backup->delete();

        return back();
    }
}
