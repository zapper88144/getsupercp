<?php

namespace App\Mcp\Tools;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class CreateCronJob extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Create a new cron job in SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'command' => 'required|string|max:255',
            'schedule' => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $cronJob = $user->cronJobs()->create([
            'command' => $validated['command'],
            'schedule' => $validated['schedule'],
            'description' => $validated['description'],
            'is_active' => true,
        ]);

        try {
            $daemon->call('update_cron_jobs', [
                'user' => $user->name,
                'jobs' => $user->cronJobs()->where('is_active', true)->get(['command', 'schedule'])->toArray(),
            ]);
        } catch (\Exception $e) {
            return Response::text('Cron job created in DB but daemon failed: '.$e->getMessage());
        }

        return Response::text("Successfully created cron job: {$cronJob->command}");
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->description('The ID of the user who owns the cron job.'),
            'command' => $schema->string()->description('The command to execute.'),
            'schedule' => $schema->string()->description('The cron schedule expression (e.g., "* * * * *").'),
            'description' => $schema->string()->description('An optional description.'),
        ];
    }
}
