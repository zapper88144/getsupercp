<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateDomain extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new web domain in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'domain' => 'required|string|unique:web_domains,domain',
            'root_path' => 'required|string',
            'php_version' => 'nullable|string',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $domain = WebDomain::create([
            'user_id' => $user->id,
            'domain' => $validated['domain'],
            'root_path' => $validated['root_path'],
            'php_version' => $validated['php_version'] ?? '8.4',
            'is_active' => true,
        ]);

        try {
            $daemon->call('create_vhost', [
                'domain' => $domain->domain,
                'user' => $user->name,
                'root' => $domain->root_path,
                'php_version' => $domain->php_version,
            ]);
        } catch (\Exception $e) {
            return Response::text('Domain created in DB but daemon failed: '.$e->getMessage());
        }

        return Response::text("Successfully created domain: {$domain->domain} and provisioned system configs.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user who owns the domain.'),
            'domain' => $schema->string()->description('The domain name (e.g., example.com).'),
            'root_path' => $schema->string()->description('The absolute path to the web root.'),
            'php_version' => $schema->string()->description('The PHP version to use (default: 8.4).'),
        ];
    }
}
