<?php

namespace App\Console\Commands;

use App\Services\BruteForceService;
use Illuminate\Console\Command;

class ClearExpiredBruteForceBlocks extends Command
{
    protected $signature = 'security:clear-expired-blocks';

    protected $description = 'Clear expired brute-force IP blocks from the database';

    public function __construct(private BruteForceService $bruteForceService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting to clear expired brute-force blocks...');

        try {
            $cleared = $this->bruteForceService->clearExpiredBlocks();

            $this->info("Successfully cleared $cleared expired brute-force blocks.");

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error clearing expired blocks: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
