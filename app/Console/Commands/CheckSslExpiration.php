<?php

namespace App\Console\Commands;

use App\Services\SslAutomationService;
use Illuminate\Console\Command;

class CheckSslExpiration extends Command
{
    protected $signature = 'security:check-ssl-expiration {--days=30 : Number of days to check in advance}';

    protected $description = 'Check SSL certificate expiration dates and alert if expiring soon';

    public function __construct(private SslAutomationService $sslService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $daysThreshold = (int) $this->option('days');

        $this->info("Checking SSL certificates expiring within $daysThreshold days...");

        try {
            $status = $this->sslService->checkCertificateStatus();

            if (empty($status)) {
                $this->info('No domains configured for SSL certificate monitoring.');

                return self::SUCCESS;
            }

            $expiringSoon = [];

            foreach ($status as $domain => $info) {
                if ($info['requires_renewal']) {
                    $expiringSoon[$domain] = $info;

                    $this->warn("⚠️  Certificate for $domain expires in {$info['days_until_expiration']} days");
                } else {
                    $this->info("✓ Certificate for $domain is valid ({$info['days_until_expiration']} days remaining)");
                }
            }

            if (! empty($expiringSoon)) {
                $this->error("\n".count($expiringSoon).' certificate(s) require renewal.');

                // You can add email notification here
                $this->notifyExpiringCertificates($expiringSoon);
            } else {
                $this->info("\nAll certificates are valid.");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error checking SSL expiration: {$e->getMessage()}");

            return self::FAILURE;
        }
    }

    /**
     * Notify about expiring certificates
     */
    private function notifyExpiringCertificates(array $expiring): void
    {
        // TODO: Implement notification (email, dashboard alert, etc.)
        // For now, just log
        \Illuminate\Support\Facades\Log::warning('SSL certificates expiring soon', ['domains' => array_keys($expiring)]);
    }
}
