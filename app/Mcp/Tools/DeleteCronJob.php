<?php

namespace App\Mcp\Tools;

use App\Models\CronJob;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class DeleteCronJob extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        Delete a cron job from SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request, RustDaemonClient $daemon): Response
    {
        $validated = $request->validate([
            'cron_job_id' => 'required|integer|exists:cron_jobs,id',
        ]);

        $cronJob = CronJob::findOrFail($validated['cron_job_id']);
        $user = $cronJob->user;

        $cronJob->delete();

        try {
            $daemon->call('update_cron_jobs', [
                'user' => $user->name,
                'jobs' => $user->cronJobs()->where('is_active', true)->get(['command', 'schedule'])->toArray(),
            ]);
        } catch (\Exception $e) {
            return Response::text('Cron job deleted from DB but daemon failed to sync: '.$e->getMessage());
        }

        return Response::text('Successfully deleted cron job.');
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'cron_job_id' => $schema->integer()->description('The ID of the cron job to delete.'),
        ];
    }
}
