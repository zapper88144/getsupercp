<?php

namespace App\Mcp\Tools;

use App\Models\FirewallRule;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateFirewallRule extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create and apply a new firewall rule in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'port' => 'required|integer|min:1|max:65535',
            'protocol' => 'required|in:tcp,udp',
            'action' => 'required|in:allow,deny',
            'source' => 'required|string',
        ]);

        $rule = FirewallRule::create($validated);

        try {
            $daemon->call('apply_firewall_rule', [
                'port' => (int) $rule->port,
                'protocol' => $rule->protocol,
                'action' => $rule->action,
                'source' => $rule->source,
            ]);
        } catch (\Exception $e) {
            return Response::text('Firewall rule created in DB but daemon failed: '.$e->getMessage());
        }

        return Response::text("Successfully created and applied firewall rule: {$rule->name}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('A descriptive name for the rule.'),
            'port' => $schema->integer()->description('The port number (1-65535).'),
            'protocol' => $schema->string()->enum(['tcp', 'udp'])->description('The protocol.'),
            'action' => $schema->string()->enum(['allow', 'deny'])->description('The action.'),
            'source' => $schema->string()->description('The source IP or "any".'),
        ];
    }
}
