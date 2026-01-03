<?php

namespace App\Http\Controllers;

use App\Models\FtpUser;
use App\Services\RustDaemonClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FtpUserController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('FtpUsers/Index', [
            'ftpUsers' => $request->user()->ftpUsers()->latest()->get(),
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

        $ftpUser = $request->user()->ftpUsers()->create($validated);

        // Sync with daemon
        $this->daemon->call('create_ftp_user', [
            'username' => $ftpUser->username,
            'password' => $request->password, // Raw password for daemon
            'homedir' => $ftpUser->homedir,
        ]);

        return redirect()->route('ftp-users.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FtpUser $ftpUser): RedirectResponse
    {
        $this->authorize('delete', $ftpUser);

        // Sync with daemon
        $this->daemon->call('delete_ftp_user', [
            'username' => $ftpUser->username,
        ]);

        $ftpUser->delete();

        return redirect()->route('ftp-users.index');
    }
}
