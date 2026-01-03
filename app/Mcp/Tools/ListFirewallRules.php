<?php

namespace App\Mcp\Tools;

use App\Models\FirewallRule;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListFirewallRules extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List all firewall rules and the current global firewall status.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $rules = FirewallRule::all();

        try {
            $response = $daemon->call('get_firewall_status');
            $status = $response['result'] ?? ['status' => 'unknown'];
        } catch (\Exception $e) {
            $status = ['status' => 'error', 'message' => $e->getMessage()];
        }

        return Response::text(json_encode([
            'global_status' => $status,
            'rules' => $rules,
        ], JSON_PRETTY_PRINT));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}
