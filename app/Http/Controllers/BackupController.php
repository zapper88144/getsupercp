<?php

namespace App\Http\Controllers;

use App\Models\Backup;
use App\Models\Database;
use App\Models\WebDomain;
use App\Services\BackupService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BackupController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(protected BackupService $backupService) {}

    public function index(Request $request): Response
    {
        $query = Backup::latest();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('source', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $allBackups = Backup::all();

        return Inertia::render('Backups/Index', [
            'backups' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
            'domains' => WebDomain::all(),
            'databases' => Database::all(),
            'stats' => [
                'totalSize' => $allBackups->sum('size'),
                'webBackups' => $allBackups->where('type', 'web')->count(),
                'dbBackups' => $allBackups->where('type', 'database')->count(),
            ],
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
        $this->authorize('update', $backup);

        try {
            $this->backupService->restore($backup);

            return back()->with('success', 'Restore completed successfully.');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Restore failed.');
        }
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'type' => 'required|in:web,database',
            'source' => 'required|string',
        ]);

        try {
            $this->backupService->createBackup(
                Auth::user(),
                $request->type,
                $request->source
            );

            return back()->with('success', 'Backup started successfully.');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Backup failed.');
        }
    }

    public function destroy(Backup $backup): RedirectResponse
    {
        $this->authorize('delete', $backup);

        try {
            $this->backupService->delete($backup);

            return back()->with('success', 'Backup deleted successfully.');
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to delete backup.');
        }
    }
}
