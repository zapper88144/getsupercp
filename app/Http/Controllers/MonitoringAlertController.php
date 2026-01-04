<?php

namespace App\Http\Controllers;

use App\Models\MonitoringAlert;
use App\Models\User;
use App\Services\MonitoringService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringAlertController extends Controller
{
    public function __construct(private MonitoringService $service) {}

    public function index(): Response|RedirectResponse
    {
        $this->authorize('viewAny', MonitoringAlert::class);

        try {
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

            $metrics = $this->service->getMetrics();

            return Inertia::render('Monitoring/Alerts', [
                'alerts' => $alerts,
                'metrics' => $metrics,
            ]);
        } catch (Exception $e) {
            return back()->with('error', 'Failed to load monitoring alerts.');
        }
    }

    public function create(): Response
    {
        $this->authorize('create', MonitoringAlert::class);

        return Inertia::render('Monitoring/CreateAlert', [
            'metrics' => ['cpu', 'memory', 'disk', 'bandwidth', 'load_average'],
            'comparisons' => ['>', '>=', '<', '<=', '==', '!='],
            'frequencies' => ['immediate', '5min', '15min', '30min', '1hour'],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', MonitoringAlert::class);

        try {
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
        } catch (Exception $e) {
            return back()->withInput()->with('error', 'Failed to create alert: '.$e->getMessage());
        }
    }

    public function edit(MonitoringAlert $alert): Response
    {
        $this->authorize('update', $alert);

        return Inertia::render('Monitoring/EditAlert', [
            'alert' => $alert,
            'metrics' => ['cpu', 'memory', 'disk', 'bandwidth', 'load_average'],
            'comparisons' => ['>', '>=', '<', '<=', '==', '!='],
            'frequencies' => ['immediate', '5min', '15min', '30min', '1hour'],
        ]);
    }

    public function update(Request $request, MonitoringAlert $alert): RedirectResponse
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

    public function toggle(MonitoringAlert $alert): RedirectResponse
    {
        $this->authorize('update', $alert);

        try {
            $alert->update(['is_enabled' => ! $alert->is_enabled]);

            return back()->with('success', 'Alert '.($alert->is_enabled ? 'enabled' : 'disabled').'.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to toggle alert: '.$e->getMessage());
        }
    }

    public function destroy(MonitoringAlert $alert): RedirectResponse
    {
        $this->authorize('delete', $alert);

        try {
            $alert->delete();

            return redirect()->route('monitoring.alerts')
                ->with('success', 'Monitoring alert deleted.');
        } catch (Exception $e) {
            return back()->with('error', 'Failed to delete alert: '.$e->getMessage());
        }
    }
}
