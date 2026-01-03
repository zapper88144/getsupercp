<?php

namespace App\Mcp\Tools;

use App\Models\FtpUser;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteFtpUser extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete an FTP user from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'ftp_user_id' => 'required|integer|exists:ftp_users,id',
        ]);

        $ftpUser = FtpUser::findOrFail($validated['ftp_user_id']);

        try {
            $daemon->call('delete_ftp_user', [
                'username' => $ftpUser->username,
            ]);
        } catch (\Exception $e) {
            return Response::text('Failed to delete FTP user from system: '.$e->getMessage());
        }

        $ftpUser->delete();

        return Response::text("Successfully deleted FTP user: {$ftpUser->username}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'ftp_user_id' => $schema->integer()->description('The ID of the FTP user to delete.'),
        ];
    }
}
