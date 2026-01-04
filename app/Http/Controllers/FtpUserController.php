<?php

namespace App\Http\Controllers;

use App\Models\FtpUser;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FtpUserController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected \App\Services\FtpService $ftpService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = $request->user()->ftpUsers();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('username', 'like', "%{$search}%");
        }

        return Inertia::render('FtpUsers/Index', [
            'ftpUsers' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('FtpUsers/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:64', 'unique:ftp_users', 'regex:/^[a-z0-9_]+$/'],
            'password' => ['required', 'string', 'min:8'],
            'homedir' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->ftpService->create($request->user(), [
                'username' => $validated['username'],
                'password' => $validated['password'],
                'home_dir' => $validated['homedir'],
            ]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to create FTP user on the system.', route('ftp-users.index'));
        }

        return redirect()->route('ftp-users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FtpUser $ftpUser): RedirectResponse
    {
        $this->authorize('delete', $ftpUser);

        try {
            $this->ftpService->delete($ftpUser);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to delete FTP user from the system.', route('ftp-users.index'));
        }

        return redirect()->route('ftp-users.index');
    }
}
