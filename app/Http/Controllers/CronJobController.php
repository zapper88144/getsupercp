<?php

namespace App\Http\Controllers;

use App\Models\CronJob;
use App\Services\CronService;
use App\Traits\HandlesDaemonErrors;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CronJobController extends Controller
{
    use HandlesDaemonErrors;

    public function __construct(
        protected CronService $cronService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $query = $request->user()->cronJobs()->latest();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search) {
                $q->where('command', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('schedule', 'like', "%{$search}%");
            });
        }

        return Inertia::render('CronJobs/Index', [
            'cronJobs' => $query->paginate(10)->withQueryString(),
            'filters' => $request->only(['search']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:255'],
            'schedule' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $this->cronService->create($request->user(), $validated);

        return redirect()->route('cron-jobs.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CronJob $cronJob): RedirectResponse
    {
        $this->authorize('update', $cronJob);

        $validated = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $this->cronService->update($cronJob, $validated);

        return redirect()->route('cron-jobs.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CronJob $cronJob): RedirectResponse
    {
        $this->authorize('delete', $cronJob);

        $this->cronService->delete($cronJob);

        return redirect()->route('cron-jobs.index');
    }
}
