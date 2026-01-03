<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateFtpUser extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new FTP user in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'username' => 'required|string|max:64|unique:ftp_users|regex:/^[a-z0-9_]+$/',
            'password' => 'required|string|min:8',
            'homedir' => 'required|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $ftpUser = $user->ftpUsers()->create([
            'username' => $validated['username'],
            'password' => $validated['password'],
            'homedir' => $validated['homedir'],
        ]);

        try {
            $daemon->call('create_ftp_user', [
                'username' => $ftpUser->username,
                'password' => $validated['password'],
                'homedir' => $ftpUser->homedir,
            ]);
        } catch (\Exception $e) {
            return Response::text('FTP user created in DB but daemon failed: '.$e->getMessage());
        }

        return Response::text("Successfully created FTP user: {$ftpUser->username}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user who owns the FTP account.'),
            'username' => $schema->string()->description('The FTP username.'),
            'password' => $schema->string()->description('The FTP password.'),
            'homedir' => $schema->string()->description('The home directory for the FTP user.'),
        ];
    }
}
