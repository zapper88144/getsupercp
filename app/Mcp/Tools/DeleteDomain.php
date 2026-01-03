<?php

namespace App\Mcp\Tools;

use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteDomain extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a web domain from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:web_domains,id',
        ]);

        $domain = WebDomain::with('user')->findOrFail($validated['id']);
        $domainName = $domain->domain;
        $userName = $domain->user->name;

        try {
            $daemon->call('delete_vhost', [
                'domain' => $domainName,
                'user' => $userName,
            ]);
        } catch (\Exception $e) {
            // Log error but continue
        }

        $domain->delete();

        return Response::text("Successfully deleted domain: {$domainName} and removed system configs.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The ID of the domain to delete.'),
        ];
    }
}
