<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateDatabase extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new database in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'name' => 'required|string|max:64|unique:databases|regex:/^[a-z0-9_]+$/',
            'db_user' => 'required|string|max:64|regex:/^[a-z0-9_]+$/',
            'db_password' => 'required|string|min:8',
            'type' => 'required|in:mysql,postgres',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $database = $user->databases()->create([
            'name' => $validated['name'],
            'db_user' => $validated['db_user'],
            'db_password' => $validated['db_password'],
            'type' => $validated['type'],
        ]);

        try {
            $daemon->call('create_database', [
                'name' => $database->name,
                'user' => $database->db_user,
                'password' => $validated['db_password'],
                'type' => $database->type,
            ]);
        } catch (\Exception $e) {
            return Response::text('Database created in DB but daemon failed: '.$e->getMessage());
        }

        return Response::text("Successfully created database: {$database->name} ({$database->type})");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user who owns the database.'),
            'name' => $schema->string()->description('The database name (lowercase, alphanumeric and underscores).'),
            'db_user' => $schema->string()->description('The database username.'),
            'db_password' => $schema->string()->description('The database password (min 8 characters).'),
            'type' => $schema->string()->enum(['mysql', 'postgres'])->description('The database type.'),
        ];
    }
}
