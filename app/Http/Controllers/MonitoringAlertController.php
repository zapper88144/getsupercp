<?php

namespace App\Http\Controllers;

use App\Models\MonitoringAlert;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringAlertController extends Controller
{
    public function index(): Response
    {
        /** @var User $user */
        $user = auth()->guard('web')->user();
        $alerts = $user->monitoringAlerts()
            ->latest()
            ->get()
            ->map(fn ($alert) => [
                ...$alert->toArray(),
                'is_triggered' => $alert->isTriggered(),
                'time_since_last_notification' => $alert->timeSinceLastNotification(),
            ]);

        return Inertia::render('Monitoring/Alerts', [
            'alerts' => $alerts,
        ]);
    }

    public function create()
    {
        return Inertia::render('Monitoring/CreateAlert', [
            'metrics' => ['cpu', 'memory', 'disk', 'bandwidth', 'load_average'],
            'comparisons' => ['>', '>=', '<', '<=', '==', '!='],
            'frequencies' => ['immediate', '5min', '15min', '30min', '1hour'],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'metric' => 'required|in:cpu,memory,disk,bandwidth,load_average',
            'threshold_percentage' => 'required|numeric|between:0,100',
            'comparison' => 'required|in:>,>=,<,<=,==,!=',
            'frequency' => 'required|in:immediate,5min,15min,30min,1hour',
            'notify_email' => 'boolean',
            'notify_webhook' => 'boolean',
            'webhook_url' => 'nullable|url',
        ]);

        /** @var User $user */
        $user = auth()->guard('web')->user();
        $user->monitoringAlerts()->create($validated + [
            'is_enabled' => true,
        ]);

        return redirect()->route('monitoring.alerts')
            ->with('success', 'Monitoring alert created.');
    }

    public function edit(MonitoringAlert $alert)
    {
        $this->authorize('update', $alert);

        return Inertia::render('Monitoring/EditAlert', [
            'alert' => $alert,
            'metrics' => ['cpu', 'memory', 'disk', 'bandwidth', 'load_average'],
            'comparisons' => ['>', '>=', '<', '<=', '==', '!='],
            'frequencies' => ['immediate', '5min', '15min', '30min', '1hour'],
        ]);
    }

    public function update(Request $request, MonitoringAlert $alert)
    {
        $this->authorize('update', $alert);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'threshold_percentage' => 'required|numeric|between:0,100',
            'notify_email' => 'boolean',
            'notify_webhook' => 'boolean',
            'webhook_url' => 'nullable|url',
        ]);

        $alert->update($validated);

        return back()->with('success', 'Monitoring alert updated.');
    }

    public function toggle(MonitoringAlert $alert)
    {
        $this->authorize('update', $alert);

        $alert->update(['is_enabled' => ! $alert->is_enabled]);

        return back()->with('success', 'Alert '.($alert->is_enabled ? 'enabled' : 'disabled').'.');
    }

    public function destroy(MonitoringAlert $alert)
    {
        $this->authorize('delete', $alert);

        $alert->delete();

        return redirect()->route('monitoring.alerts')
            ->with('success', 'Monitoring alert deleted.');
    }
}
