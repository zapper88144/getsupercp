<?php

namespace App\Console\Commands;

use App\Services\RustDaemonClient;
use Illuminate\Console\Command;

class ReloadServices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:reload-services';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reload Nginx and PHP-FPM services via the Rust daemon';

    /**
     * Execute the console command.
     */
    public function handle(RustDaemonClient $daemon)
    {
        $this->info('Requesting service reload...');

        try {
            $result = $daemon->call('reload_services');
            $this->info($result['result'] ?? 'Success');
        } catch (\Exception $e) {
            $this->error('Failed to reload services: '.$e->getMessage());

            return 1;
        }

        return 0;
    }
}
