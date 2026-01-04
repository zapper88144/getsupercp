<?php

namespace App\Jobs;

use App\Models\Database;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class UpdateResourceUsage implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(RustDaemonClient $daemon): void
    {
        // Update Database Sizes
        $databases = Database::all();
        foreach ($databases as $database) {
            try {
                $size = $daemon->call('get_database_size', ['name' => $database->name]);
                if (is_numeric($size)) {
                    $database->update(['size_bytes' => (int) $size]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to update size for database {$database->name}: ".$e->getMessage());
            }
        }

        // Update Web Domain Sizes
        $domains = WebDomain::all();
        foreach ($domains as $domain) {
            try {
                $size = $daemon->call('get_directory_size', ['path' => $domain->root_path]);
                if (is_numeric($size)) {
                    $domain->update(['size_bytes' => (int) $size]);
                }
            } catch (\Exception $e) {
                Log::error("Failed to update size for domain {$domain->domain}: ".$e->getMessage());
            }
        }
    }
}
