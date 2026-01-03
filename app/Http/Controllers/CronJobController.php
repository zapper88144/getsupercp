<?php

namespace App\Http\Controllers;

use App\Models\CronJob;
use App\Services\RustDaemonClient;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CronJobController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        return Inertia::render('CronJobs/Index', [
            'cronJobs' => $request->user()->cronJobs()->latest()->get(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'command' => ['required', 'string', 'max:255'],
            'schedule' => ['required', 'string', 'max:100'], // Basic validation, could be improved
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $cronJob = $request->user()->cronJobs()->create($validated);

        // Sync with daemon
        $this->daemon->call('update_cron_jobs', [
            'user' => $request->user()->name,
            'jobs' => $request->user()->cronJobs()->where('is_active', true)->get(['command', 'schedule'])->toArray(),
        ]);

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

        $cronJob->update($validated);

        // Sync with daemon
        $this->daemon->call('update_cron_jobs', [
            'user' => $request->user()->name,
            'jobs' => $request->user()->cronJobs()->where('is_active', true)->get(['command', 'schedule'])->toArray(),
        ]);

        return redirect()->route('cron-jobs.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CronJob $cronJob): RedirectResponse
    {
        $this->authorize('delete', $cronJob);

        $user = $cronJob->user;
        $cronJob->delete();

        // Sync with daemon
        $this->daemon->call('update_cron_jobs', [
            'user' => $user->name,
            'jobs' => $user->cronJobs()->where('is_active', true)->get(['command', 'schedule'])->toArray(),
        ]);

        return redirect()->route('cron-jobs.index');
    }
}
