<?php

namespace App\Http\Controllers;

use App\Models\BackupSchedule;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BackupScheduleController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $schedules = $user->backupSchedules()
            ->latest()
            ->get()
            ->map(fn ($schedule) => [
                ...$schedule->toArray(),
                'success_rate' => round($schedule->successRate(), 1),
                'next_run_in' => $schedule->nextRunIn(),
            ]);

        return Inertia::render('Backups/Schedules', [
            'schedules' => $schedules,
        ]);
    }

    public function create()
    {
        return Inertia::render('Backups/CreateSchedule', [
            'frequencies' => ['daily', 'weekly', 'monthly', 'custom'],
            'types' => ['full', 'incremental', 'database_only', 'files_only'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'time' => 'required|date_format:H:i',
            'day_of_week' => 'nullable|numeric|between:0,6',
            'day_of_month' => 'nullable|numeric|between:1,31',
            'backup_type' => 'required|in:full,incremental,database_only,files_only',
            'targets' => 'nullable|json',
            'retention_days' => 'required|numeric|min:1|max:3650',
            'compress' => 'boolean',
            'encrypt' => 'boolean',
            'notify_on_completion' => 'boolean',
            'notify_on_failure' => 'boolean',
        ]);

        /** @var User $user */
        $user = auth()->guard('web')->user();
        $schedule = $user->backupSchedules()->create($validated + [
            'next_run_at' => $this->calculateNextRunTime($validated),
        ]);

        return redirect()->route('backups.schedules')
            ->with('success', 'Backup schedule created.');
    }

    public function edit(BackupSchedule $schedule)
    {
        $this->authorize('update', $schedule);

        return Inertia::render('Backups/EditSchedule', [
            'schedule' => $schedule,
            'frequencies' => ['daily', 'weekly', 'monthly', 'custom'],
            'types' => ['full', 'incremental', 'database_only', 'files_only'],
        ]);
    }

    public function update(Request $request, BackupSchedule $schedule)
    {
        $this->authorize('update', $schedule);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'frequency' => 'required|in:daily,weekly,monthly,custom',
            'time' => 'required|date_format:H:i',
            'retention_days' => 'required|numeric|min:1',
            'is_enabled' => 'boolean',
        ]);

        $schedule->update($validated);

        return back()->with('success', 'Backup schedule updated.');
    }

    public function toggle(BackupSchedule $schedule)
    {
        $this->authorize('update', $schedule);

        $schedule->update(['is_enabled' => ! $schedule->is_enabled]);

        return back()->with('success', 'Backup schedule '.($schedule->is_enabled ? 'enabled' : 'disabled').'.');
    }

    public function destroy(BackupSchedule $schedule)
    {
        $this->authorize('delete', $schedule);

        $schedule->delete();

        return redirect()->route('backups.schedules')
            ->with('success', 'Backup schedule deleted.');
    }

    private function calculateNextRunTime(array $data)
    {
        $time = $data['time'];
        $frequency = $data['frequency'];

        [$hours, $minutes] = explode(':', $time);

        $next = today()->setHour((int) $hours)->setMinute((int) $minutes)->setSecond(0);

        if ($next->isPast()) {
            if ($frequency === 'daily') {
                $next = $next->addDays(1);
            } elseif ($frequency === 'weekly') {
                $next = $next->addWeeks(1);
            } elseif ($frequency === 'monthly') {
                $next = $next->addMonths(1);
            } else {
                $next = $next->addDays(1);
            }
        }

        return $next;
    }
}
