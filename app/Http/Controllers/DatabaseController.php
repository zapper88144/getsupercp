<?php

namespace App\Http\Controllers;

use App\Models\Database;
use App\Services\DatabaseService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DatabaseController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected DatabaseService $databaseService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = $request->user()->databases();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('db_user', 'like', "%{$search}%");
            });
        }

        return Inertia::render('Databases/Index', [
            'databases' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
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

        try {
            $this->databaseService->create($request->user(), [
                'name' => $validated['name'],
                'db_user' => $validated['db_user'],
                'password' => $validated['db_password'],
                'engine' => $validated['type'],
            ]);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to create database on the system.', route('databases.index'));
        }

        return redirect()->route('databases.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Database $database): RedirectResponse
    {
        $this->authorize('delete', $database);

        try {
            $this->databaseService->delete($database);
        } catch (\Throwable $e) {
            return $this->handleDaemonError($e, 'Failed to delete database from the system.', route('databases.index'));
        }

        return redirect()->route('databases.index');
    }
}
