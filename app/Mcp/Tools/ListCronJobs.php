<?php

namespace App\Mcp\Tools;

use App\Models\CronJob;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

class ListCronJobs extends Tool
{
    /**
     * The tool's description.
     */
    protected string $description = <<<'MARKDOWN'
        List all cron jobs managed by SuperCP.
    MARKDOWN;

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $cronJobs = CronJob::with('user:id,name')->get();

        if ($cronJobs->isEmpty()) {
            return Response::text('No cron jobs found.');
        }

        return Response::text($cronJobs->toJson(JSON_PRETTY_PRINT));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            //
        ];
    }
}
