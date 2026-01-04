<?php

namespace App\Console\Commands;

use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Console\Command;

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
    public function handle(RustDaemonClient $daemon): int
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
                if ($this->renewCertificate($webDomain, $daemon)) {
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
        $expiresIn = now()->diffInDays($webDomain->ssl_expires_at, false);

        return $expiresIn <= 30;
    }

    /**
     * Attempt to renew a certificate for a domain.
     */
    protected function renewCertificate(WebDomain $webDomain, RustDaemonClient $daemon): bool
    {
        try {
            // Use the daemon to request/renew the certificate
            $daemon->requestSslCert($webDomain->domain, $webDomain->user->email ?? 'admin@example.com');

            // The daemon handles certbot and nginx reload.
            // Now we just need to update the expiration date in our database.

            // Path to the certificate (usually in /etc/letsencrypt/live/domain/fullchain.pem)
            $certPath = "/etc/letsencrypt/live/{$webDomain->domain}/fullchain.pem";

            // Since the web server might not have read access to /etc/letsencrypt,
            // we can ask the daemon to read the cert info or just use openssl if we have access.
            // For now, let's try to read it via the daemon's readFile method if direct access fails.

            $certContent = null;
            if (file_exists($certPath)) {
                $certContent = file_get_contents($certPath);
            } else {
                try {
                    $certContent = $daemon->readFile($certPath);
                } catch (\Exception $e) {
                    $this->warn("Could not read certificate file via daemon: {$e->getMessage()}");
                }
            }

            if ($certContent) {
                $certData = openssl_x509_parse($certContent);
                if ($certData && isset($certData['validTo_time_t'])) {
                    $webDomain->update([
                        'ssl_expires_at' => \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t']),
                    ]);
                }
            }

            return true;
        } catch (\Exception $e) {
            $this->error("Exception during renewal for {$webDomain->domain}: {$e->getMessage()}");

            return false;
        }
    }
}
