<?php

namespace App\Mcp\Tools;

use App\Models\FirewallRule;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteFirewallRule extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a firewall rule from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'rule_id' => 'required|integer|exists:firewall_rules,id',
        ]);

        $rule = FirewallRule::findOrFail($validated['rule_id']);

        try {
            $daemon->call('delete_firewall_rule', [
                'port' => (int) $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
            ]);
        } catch (\Exception $e) {
            return Response::text('Failed to delete firewall rule from system: '.$e->getMessage());
        }

        $rule->delete();

        return Response::text("Successfully deleted firewall rule: {$rule->name}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'rule_id' => $schema->integer()->description('The ID of the firewall rule to delete.'),
        ];
    }
}
