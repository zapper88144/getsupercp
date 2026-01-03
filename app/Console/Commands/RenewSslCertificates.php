<?php

namespace App\Console\Commands;

use App\Models\WebDomain;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

class RenewSslCertificates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:renew-ssl-certificates
                            {--force : Force renewal of all certificates}
                            {--domain= : Renew a specific domain}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically renew SSL certificates expiring within 30 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Starting SSL certificate renewal process...');

        $query = WebDomain::where('has_ssl', true);

        // If specific domain requested, filter to that
        if ($domain = $this->option('domain')) {
            $query->where('domain', $domain);
        }

        $domains = $query->get();

        if ($domains->isEmpty()) {
            $this->info('No SSL domains found to renew.');

            return self::SUCCESS;
        }

        $renewed = 0;
        $skipped = 0;

        foreach ($domains as $webDomain) {
            if ($this->shouldRenew($webDomain)) {
                if ($this->renewCertificate($webDomain)) {
                    $renewed++;
                    $this->info("✅ Renewed certificate for {$webDomain->domain}");
                } else {
                    $this->error("❌ Failed to renew certificate for {$webDomain->domain}");
                }
            } else {
                $skipped++;
                if ($this->option('verbose')) {
                    $this->line("⏭️  Skipped {$webDomain->domain} - certificate still valid");
                }
            }
        }

        $this->info("SSL renewal complete: {$renewed} renewed, {$skipped} skipped.");

        return self::SUCCESS;
    }

    /**
     * Determine if a domain's certificate should be renewed.
     */
    protected function shouldRenew(WebDomain $webDomain): bool
    {
        // Force renewal if requested
        if ($this->option('force')) {
            return true;
        }

        // If no expiration date is set, attempt renewal
        if (! $webDomain->ssl_expires_at) {
            return true;
        }

        // Renew if expiring within 30 days
        $expiresIn = now()->diffInDays($webDomain->ssl_expires_at);

        return $expiresIn <= 30;
    }

    /**
     * Attempt to renew a certificate for a domain.
     */
    protected function renewCertificate(WebDomain $webDomain): bool
    {
        try {
            // Use certbot directly to renew the certificate
            $result = Process::run("certbot renew --cert-name {$webDomain->domain} --non-interactive --agree-tos --no-eff-email 2>&1");

            if ($result->exitCode() !== 0) {
                $this->warn("Certbot output: {$result->output()}");

                return false;
            }

            // Read the certificate to extract expiration date
            if (file_exists($webDomain->ssl_certificate_path)) {
                $certData = openssl_x509_parse(file_get_contents($webDomain->ssl_certificate_path));
                if ($certData && isset($certData['validTo_time_t'])) {
                    $webDomain->update([
                        'ssl_expires_at' => \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t']),
                    ]);
                }
            }

            // Reload nginx to apply new certificate
            Process::run('systemctl reload nginx 2>&1');

            return true;
        } catch (\Exception $e) {
            $this->error("Exception during renewal: {$e->getMessage()}");

            return false;
        }
    }
}
