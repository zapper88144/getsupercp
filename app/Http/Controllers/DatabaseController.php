<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Services\RustDaemonClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('Databases/Index', [
            'databases' => $request->user()->databases()->latest()->get(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        return Inertia::render('Databases/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:64', 'unique:databases', 'regex:/^[a-z0-9_]+$/'],
            'db_user' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9_]+$/'],
            'db_password' => ['required', 'string', 'min:8'],
            'type' => ['required', 'in:mysql,postgres'],
        ]);

        $database = $request->user()->databases()->create($validated);

        // Sync with daemon
        $this->daemon->call('create_database', [
            'name' => $database->name,
            'user' => $database->db_user,
            'password' => $request->db_password, // Use raw password for daemon
            'type' => $database->type,
        ]);

        return redirect()->route('databases.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Database $database): RedirectResponse
    {
        $this->authorize('delete', $database);

        // Sync with daemon
        $this->daemon->call('delete_database', [
            'name' => $database->name,
            'type' => $database->type,
        ]);

        $database->delete();

        return redirect()->route('databases.index');
    }
}
