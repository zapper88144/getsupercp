<?php

namespace App\Mcp\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListNotifications extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List notifications for a specific user in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'unread_only' => 'nullable|boolean',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $query = $user->notifications();

        if ($validated['unread_only'] ?? false) {
            $query->unread();
        }

        $notifications = $query->latest()->limit($validated['limit'] ?? 50)->get();

        if ($notifications->isEmpty()) {
            return Response::text('No notifications found.');
        }

        return Response::text($notifications->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user to list notifications for.'),
            'unread_only' => $schema->boolean()->description('Whether to only list unread notifications.')->default(false),
            'limit' => $schema->integer()->description('Number of notifications to retrieve (default: 50, max: 100).')->default(50),
        ];
    }
}
