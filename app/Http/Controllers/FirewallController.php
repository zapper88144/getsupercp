<?php

namespace App\Http\Controllers;

use App\Models\FirewallRule;
use App\Services\FirewallService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FirewallController extends Controller
{
    public function __construct(
        private FirewallService $firewallService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', FirewallRule::class);

        $status = $this->firewallService->getStatus();

        return Inertia::render('Firewall/Index', [
            'rules' => FirewallRule::latest()->get(),
            'status' => $status,
        ]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', FirewallRule::class);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'protocol' => 'required|in:tcp,udp',
            'action' => 'required|in:allow,deny',
            'source' => 'required|string',
        ]);

        try {
            $this->firewallService->createRule($validated);

            return back()->with('success', 'Firewall rule created and applied.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function destroy(FirewallRule $rule)
    {
        $this->authorize('delete', $rule);

        try {
            $this->firewallService->deleteRule($rule);

            return back()->with('success', 'Firewall rule deleted.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function toggle(FirewallRule $rule, Request $request)
    {
        $this->authorize('update', $rule);

        try {
            $this->firewallService->toggleRule($rule);

            return back()->with('success', 'Firewall rule status updated.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function toggleGlobal(Request $request)
    {
        $validated = $request->validate([
            'enable' => 'required|boolean',
        ]);

        try {
            if ($validated['enable']) {
                $this->firewallService->enable();
            } else {
                $this->firewallService->disable();
            }

            return back()->with('success', 'Firewall '.($validated['enable'] ? 'enabled' : 'disabled').'.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }
}
