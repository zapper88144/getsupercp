<?php

namespace App\Console\Commands;

use App\Events\SystemStatsUpdated;
use App\Services\MonitoringService;
use Illuminate\Console\Command;

class BroadcastSystemStats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:broadcast-system-stats {--watch : Run in a loop} {--interval=2 : Interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch system stats and broadcast them via WebSockets';

    /**
     * Execute the console command.
     */
    public function handle(MonitoringService $monitoringService): void
    {
        $watch = $this->option('watch');
        $interval = (int) $this->option('interval');

        $this->info('Starting system stats broadcast...');

        do {
            try {
                $stats = $monitoringService->getSystemStats();
                SystemStatsUpdated::dispatch($stats);
                $this->info('Stats broadcasted at '.now()->toTimeString());
            } catch (\Throwable $e) {
                $this->error('Failed to broadcast stats: '.$e->getMessage());
            }

            if ($watch) {
                sleep($interval);
            }
        } while ($watch);
    }
}
