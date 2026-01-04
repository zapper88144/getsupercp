<?php

namespace App\Console\Commands;

use App\Services\IpWhitelistService;
use Illuminate\Console\Command;

class RefreshCloudflareIps extends Command
{
    protected $signature = 'security:refresh-cloudflare-ips';

    protected $description = 'Refresh and sync Cloudflare IP ranges in the whitelist';

    public function __construct(private IpWhitelistService $ipWhitelistService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $this->info('Starting to refresh Cloudflare IP ranges...');

        try {
            $added = $this->ipWhitelistService->addCloudflareIps();

            $this->info('Successfully added/updated '.count($added).' Cloudflare IP ranges.');

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error refreshing Cloudflare IPs: {$e->getMessage()}");

            return self::FAILURE;
        }
    }
}
