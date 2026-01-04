<?php

namespace App\Mcp\Tools;

use App\Models\EmailAccount;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Hash;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateEmailAccount extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new email account in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:8',
            'quota_mb' => 'integer|min:1',
        ]);

        $email = $validated['email'];
        $password = $validated['password'];
        $quotaMb = $validated['quota_mb'] ?? 1024;

        // Extract domain from email
        $parts = explode('@', $email);
        [, $domain] = $parts;

        // Verify domain exists
        if (! WebDomain::where('domain', $domain)->exists()) {
            return Response::text("Domain {$domain} not found in SuperCP.");
        }

        if (EmailAccount::where('email', $email)->exists()) {
            return Response::text("Email account {$email} already exists.");
        }

        $account = EmailAccount::create([
            'email' => $email,
            'password' => Hash::make($password),
            'quota_mb' => $quotaMb,
            'status' => 'active',
        ]);

        try {
            $daemon = app(RustDaemonClient::class);
            $daemon->call('update_email_account', [
                'email' => $account->email,
                'password' => $password,
                'quota_mb' => $account->quota_mb,
            ]);
        } catch (\Exception $e) {
            return Response::text("Email account created but sync failed: {$e->getMessage()}");
        }

        return Response::text("Email account {$email} created successfully.");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'email' => $schema->string('The full email address (e.g., user@example.com).'),
            'password' => $schema->string('The password for the email account (min 8 characters).'),
            'quota_mb' => $schema->integer('The storage quota in MB (default 1024).'),
        ];
    }
}
