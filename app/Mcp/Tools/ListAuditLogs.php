<?php

namespace App\Mcp\Tools;

use App\Models\AuditLog;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListAuditLogs extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List audit logs from SuperCP. Useful for tracking actions performed by users or the system.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'nullable|integer|exists:users,id',
            'action' => 'nullable|string',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $query = AuditLog::query()->with('user:id,name,email')->latest();

        if (isset($validated['user_id'])) {
            $query->where('user_id', $validated['user_id']);
        }

        if (isset($validated['action'])) {
            $query->where('action', 'like', "%{$validated['action']}%");
        }

        $logs = $query->limit($validated['limit'] ?? 50)->get();

        if ($logs->isEmpty()) {
            return Response::text('No audit logs found.');
        }

        return Response::text($logs->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('Filter logs by user ID.'),
            'action' => $schema->string()->description('Filter logs by action name (e.g., "create_domain").'),
            'limit' => $schema->integer()->description('Number of logs to retrieve (default: 50, max: 100).')->default(50),
        ];
    }
}
