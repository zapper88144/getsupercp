<?php

namespace App\Services;

use App\Models\FirewallRule;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;

class FirewallService
{
    use HandlesDaemonErrors;

    private RustDaemonClient $daemon;

    public function __construct(?RustDaemonClient $daemon = null)
    {
        $this->daemon = $daemon ?? new RustDaemonClient;
    }

    /**
     * Get firewall status
     */
    public function getStatus(): array
    {
        try {
            return (array) $this->daemon->call('get_firewall_status');
        } catch (Exception $e) {
            Log::error('Failed to get firewall status', ['error' => $e->getMessage()]);

            return ['enabled' => false, 'rules_count' => 0];
        }
    }

    /**
     * Enable firewall
     */
    public function enable(): bool
    {
        return $this->handleDaemonCall(function () {
            $this->daemon->call('toggle_firewall', ['enable' => true]);
            Log::info('Firewall enabled');

            return true;
        }, 'Failed to enable firewall');
    }

    /**
     * Disable firewall
     */
    public function disable(): bool
    {
        return $this->handleDaemonCall(function () {
            $this->daemon->call('toggle_firewall', ['enable' => false]);
            Log::info('Firewall disabled');

            return true;
        }, 'Failed to disable firewall');
    }

    /**
     * Create a firewall rule
     */
    public function createRule(array $data): FirewallRule
    {
        // Validate rule parameters
        $this->validateRule($data);

        return $this->handleDaemonCall(function () use ($data) {
            // Apply rule via daemon
            $this->daemon->call('apply_firewall_rule', [
                'port' => (int) $data['port'],
                'protocol' => $data['protocol'] ?? 'tcp',
                'action' => $data['action'] ?? 'allow',
                'source' => $data['source'] ?? 'any',
            ]);

            Log::info('Firewall rule applied on daemon', [
                'port' => $data['port'],
            ]);

            // Create database record
            return FirewallRule::create([
                'port' => $data['port'],
                'protocol' => $data['protocol'] ?? 'tcp',
                'action' => $data['action'] ?? 'allow',
                'source' => $data['source'] ?? 'any',
                'name' => $data['name'] ?? null,
                'is_active' => true,
            ]);
        }, "Failed to create firewall rule for port: {$data['port']}");
    }

    /**
     * Update a firewall rule
     */
    public function updateRule(FirewallRule $rule, array $data): FirewallRule
    {
        return $this->handleDaemonCall(function () use ($rule, $data) {
            // Delete old rule first
            $this->daemon->call('delete_firewall_rule', [
                'port' => (int) $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
            ]);

            // Apply new rule
            $this->daemon->call('apply_firewall_rule', [
                'port' => (int) ($data['port'] ?? $rule->port),
                'protocol' => $data['protocol'] ?? $rule->protocol,
                'action' => $action = $data['action'] ?? $rule->action,
                'source' => $data['source'] ?? $rule->source,
            ]);

            $rule->update($data);

            Log::info('Firewall rule updated', [
                'port' => $rule->port,
                'protocol' => $rule->protocol,
            ]);

            return $rule->fresh();
        }, "Failed to update firewall rule for port: {$rule->port}");
    }

    /**
     * Delete a firewall rule
     */
    public function deleteRule(FirewallRule $rule): bool
    {
        return $this->handleDaemonCall(function () use ($rule) {
            // Delete from daemon
            $this->daemon->call('delete_firewall_rule', [
                'port' => (int) $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
            ]);

            Log::info('Firewall rule deleted from daemon', [
                'port' => $rule->port,
                'protocol' => $rule->protocol,
            ]);

            // Delete from database
            return $rule->delete();
        }, "Failed to delete firewall rule for port: {$rule->port}");
    }

    /**
     * Toggle a firewall rule
     */
    public function toggleRule(FirewallRule $rule): FirewallRule
    {
        return $this->handleDaemonCall(function () use ($rule) {
            $rule->is_active = ! $rule->is_active;
            $rule->save();

            $this->syncRule($rule);

            Log::info('Firewall rule toggled', [
                'port' => $rule->port,
                'is_active' => $rule->is_active,
            ]);

            return $rule->fresh();
        }, "Failed to toggle firewall rule for port: {$rule->port}");
    }

    /**
     * Sync a firewall rule with the daemon
     */
    public function syncRule(FirewallRule $rule): void
    {
        $this->handleDaemonCall(function () use ($rule) {
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
        }, "Failed to sync firewall rule for port: {$rule->port}");
    }

    /**
     * Validate firewall rule parameters
     */
    private function validateRule(array $data): void
    {
        // Validate port
        if (! isset($data['port']) || ! is_numeric($data['port'])) {
            throw new Exception('Invalid port number');
        }

        $port = (int) $data['port'];
        if ($port < 1 || $port > 65535) {
            throw new Exception('Port must be between 1 and 65535');
        }

        // Validate protocol
        $protocol = $data['protocol'] ?? 'tcp';
        if (! in_array($protocol, ['tcp', 'udp'])) {
            throw new Exception('Invalid protocol. Must be tcp or udp');
        }

        // Validate action
        $action = $data['action'] ?? 'allow';
        if (! in_array($action, ['allow', 'deny', 'reject'])) {
            throw new Exception('Invalid action. Must be allow, deny, or reject');
        }
    }

    /**
     * Check if daemon is running
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
