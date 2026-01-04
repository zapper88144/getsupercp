<?php

namespace App\Jobs;

use App\Models\DnsZone;
use App\Services\CloudflareService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncDnsToCloudflare implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(public DnsZone $dnsZone)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(CloudflareService $cloudflareService): void
    {
        $cloudflareService->syncDnsRecords($this->dnsZone);
    }
}
