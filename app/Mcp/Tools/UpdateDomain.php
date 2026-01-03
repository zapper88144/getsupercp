<?php

namespace App\Mcp\Tools;

use App\Models\WebDomain;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class UpdateDomain extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Update an existing web domain in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'id' => 'required|integer|exists:web_domains,id',
            'domain' => 'nullable|string|unique:web_domains,domain,'.$request->get('id'),
            'root_path' => 'nullable|string',
            'php_version' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $domain = WebDomain::findOrFail($validated['id']);
        $domain->update(array_filter($validated));

        return Response::text("Successfully updated domain: {$domain->domain}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'id' => $schema->integer()->description('The ID of the domain to update.'),
            'domain' => $schema->string()->description('The new domain name.'),
            'root_path' => $schema->string()->description('The new absolute path to the web root.'),
            'php_version' => $schema->string()->description('The new PHP version.'),
            'is_active' => $schema->boolean()->description('Whether the domain is active.'),
        ];
    }
}
