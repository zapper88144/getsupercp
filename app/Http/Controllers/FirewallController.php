<?php

namespace App\Http\Controllers;

use App\Models\FirewallRule;
use App\Services\RustDaemonClient;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FirewallController extends Controller
{
    public function __construct(
        protected RustDaemonClient $daemon
    ) {}

    public function index(): Response
    {
        $response = $this->daemon->call('get_firewall_status');
        $status = $response['result'] ?? ['status' => 'unknown', 'rules' => []];

        return Inertia::render('Firewall/Index', [
            'rules' => FirewallRule::latest()->get(),
            'status' => $status,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'protocol' => 'required|in:tcp,udp',
            'action' => 'required|in:allow,deny',
            'source' => 'required|string',
        ]);

        $rule = FirewallRule::create($validated);

        $this->daemon->call('apply_firewall_rule', [
            'port' => (int) $rule->port,
            'protocol' => $rule->protocol,
            'action' => $rule->action,
            'source' => $rule->source,
        ]);

        return back()->with('success', 'Firewall rule created and applied.');
    }

    public function destroy(FirewallRule $rule)
    {
        $this->daemon->call('delete_firewall_rule', [
            'port' => (int) $rule->port,
            'protocol' => $rule->protocol,
            'action' => $rule->action,
        ]);

        $rule->delete();

        return back()->with('success', 'Firewall rule deleted.');
    }

    public function toggle(FirewallRule $rule)
    {
        $rule->is_active = !$rule->is_active;
        $rule->save();

        if ($rule->is_active) {
            $this->daemon->call('apply_firewall_rule', [
                'port' => (int) $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
                'source' => $rule->source,
            ]);
        } else {
            $this->daemon->call('delete_firewall_rule', [
                'port' => (int) $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
            ]);
        }

        return back()->with('success', 'Firewall rule status updated.');
    }

    public function toggleGlobal(Request $request)
    {
        $validated = $request->validate([
            'enable' => 'required|boolean',
        ]);

        $this->daemon->call('toggle_firewall', [
            'enable' => $validated['enable'],
        ]);

        return back()->with('success', 'Firewall ' . ($validated['enable'] ? 'enabled' : 'disabled') . '.');
    }
}
