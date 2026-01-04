<?php

namespace App\Console\Commands;

use App\Events\LogUpdated;
use App\Services\LogService;
use Illuminate\Console\Command;

class TailLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:tail-logs {type : The log type to tail} {--interval=1 : Interval in seconds}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Tail a log file and broadcast updates via WebSockets';

    /**
     * Execute the console command.
     */
    public function handle(LogService $logService): void
    {
        $type = $this->argument('type');
        $interval = (int) $this->option('interval');

        $this->info("Tailing {$type} logs...");

        $lastContent = '';

        while (true) {
            try {
                $content = $logService->getLogs($type, 50);

                if ($content !== $lastContent) {
                    LogUpdated::dispatch($type, $content);
                    $lastContent = $content;
                    $this->info("Broadcasted update for {$type} at ".now()->toTimeString());
                }
            } catch (\Throwable $e) {
                $this->error("Failed to tail {$type} logs: ".$e->getMessage());
            }

            sleep($interval);
        }
    }
}
