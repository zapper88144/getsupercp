<?php

namespace App\Mcp\Tools;

use App\Models\EmailAccount;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteEmailAccount extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete an email account from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'account_id' => 'required|integer|exists:email_accounts,id',
        ]);

        $account = EmailAccount::findOrFail($validated['account_id']);

        $email = $account->email;

        try {
            $daemon = app(RustDaemonClient::class);
            $daemon->call('delete_email_account', [
                'email' => $email,
            ]);
        } catch (\Exception $e) {
            return Response::text("Failed to sync deletion: {$e->getMessage()}");
        }

        $account->delete();

        return Response::text("Email account {$email} deleted successfully.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'account_id' => $schema->integer('The ID of the email account to delete.'),
        ];
    }
}
