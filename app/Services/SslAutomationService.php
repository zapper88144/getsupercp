<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Log;

class SslAutomationService
{
    private string $letsEncryptBaseUrl = 'https://acme-v02.api.letsencrypt.org/directory';

    private string $letsEncryptStagingUrl = 'https://acme-staging-v02.api.letsencrypt.org/directory';

    private CloudflareApiService $cloudflareService;

    public function __construct(CloudflareApiService $cloudflareService)
    {
        $this->cloudflareService = $cloudflareService;
    }

    /**
     * Check if SSL certificate exists and is valid
     */
    public function isCertificateValid(string $domain): bool
    {
        try {
            $certPath = $this->getCertificatePath($domain);

            if (! file_exists($certPath)) {
                return false;
            }

            $cert = openssl_x509_read(file_get_contents($certPath));

            if (! $cert) {
                return false;
            }

            $info = openssl_x509_parse($cert);

            if (! isset($info['validTo_time_t'])) {
                return false;
            }

            // Certificate is valid if expiration is more than 7 days away
            return $info['validTo_time_t'] > time() + (7 * 24 * 60 * 60);
        } catch (Exception $e) {
            Log::error('Failed to check certificate validity', ['error' => $e->getMessage(), 'domain' => $domain]);

            return false;
        }
    }

    /**
     * Get certificate expiration date
     */
    public function getCertificateExpiration(string $domain): ?string
    {
        try {
            $certPath = $this->getCertificatePath($domain);

            if (! file_exists($certPath)) {
                return null;
            }

            $cert = openssl_x509_read(file_get_contents($certPath));

            if (! $cert) {
                return null;
            }

            $info = openssl_x509_parse($cert);

            if (! isset($info['validTo_time_t'])) {
                return null;
            }

            return date('Y-m-d H:i:s', $info['validTo_time_t']);
        } catch (Exception $e) {
            Log::error('Failed to get certificate expiration', ['error' => $e->getMessage(), 'domain' => $domain]);

            return null;
        }
    }

    /**
     * Get certificate path for domain
     */
    private function getCertificatePath(string $domain): string
    {
        $certDir = config('services.ssl.cert_dir') ?? '/etc/letsencrypt/live';

        return "$certDir/$domain/fullchain.pem";
    }

    /**
     * Get private key path for domain
     */
    private function getPrivateKeyPath(string $domain): string
    {
        $keyDir = config('services.ssl.key_dir') ?? '/etc/letsencrypt/live';

        return "$keyDir/$domain/privkey.pem";
    }

    /**
     * Provision new Let's Encrypt certificate
     */
    public function provisionLetsEncryptCertificate(string $domain, array $altDomains = []): array
    {
        try {
            Log::info('Provisioning Let\'s Encrypt certificate', ['domain' => $domain, 'alt_domains' => $altDomains]);

            $domains = [$domain, ...$altDomains];

            // Use certbot to provision certificate
            // This is a simplified version - in production, use ACME client library
            $command = sprintf(
                'certbot certonly --non-interactive --agree-tos --no-eff-email --email %s --webroot -w %s -d %s',
                escapeshellarg(config('services.ssl.email')),
                escapeshellarg(config('services.ssl.webroot') ?? '/var/www/html'),
                escapeshellarg(implode(',', $domains))
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new Exception('Certbot failed: '.implode("\n", $output));
            }

            Log::info('Successfully provisioned Let\'s Encrypt certificate', ['domain' => $domain]);

            return [
                'domain' => $domain,
                'alt_domains' => $altDomains,
                'cert_path' => $this->getCertificatePath($domain),
                'key_path' => $this->getPrivateKeyPath($domain),
                'expiration' => $this->getCertificateExpiration($domain),
            ];
        } catch (Exception $e) {
            Log::error('Failed to provision Let\'s Encrypt certificate', ['error' => $e->getMessage(), 'domain' => $domain]);
            throw $e;
        }
    }

    /**
     * Provision Cloudflare Origin Certificate
     */
    public function provisionCloudflareOriginCertificate(string $domain, array $altDomains = []): array
    {
        try {
            Log::info('Provisioning Cloudflare Origin Certificate', ['domain' => $domain]);

            $hostnames = [$domain, "www.$domain", ...$altDomains];

            // Get certificate from Cloudflare
            $certificate = $this->cloudflareService->createOriginCertificate($hostnames);

            if (! isset($certificate['certificate'], $certificate['private_key'])) {
                throw new Exception('Invalid certificate response from Cloudflare');
            }

            // Save certificate
            $certPath = $this->getCertificatePath($domain);
            $keyPath = $this->getPrivateKeyPath($domain);

            $this->ensureDirectoryExists(dirname($certPath));

            file_put_contents($certPath, $certificate['certificate']);
            chmod($certPath, 0644);

            file_put_contents($keyPath, $certificate['private_key']);
            chmod($keyPath, 0600);

            Log::info('Successfully provisioned Cloudflare Origin Certificate', [
                'domain' => $domain,
                'cert_id' => $certificate['id'] ?? null,
            ]);

            return [
                'domain' => $domain,
                'alt_domains' => $altDomains,
                'cert_path' => $certPath,
                'key_path' => $keyPath,
                'cert_id' => $certificate['id'] ?? null,
                'expiration' => $this->getCertificateExpiration($domain),
            ];
        } catch (Exception $e) {
            Log::error('Failed to provision Cloudflare Origin Certificate', ['error' => $e->getMessage(), 'domain' => $domain]);
            throw $e;
        }
    }

    /**
     * Renew expiring certificates
     */
    public function renewExpiringCertificates(int $daysThreshold = 30): array
    {
        try {
            $domains = $this->getConfiguredDomains();
            $renewed = [];

            foreach ($domains as $domain) {
                $expiration = $this->getCertificateExpiration($domain);

                if (! $expiration) {
                    continue;
                }

                $expiresIn = strtotime($expiration) - time();
                $daysUntilExpiration = $expiresIn / (24 * 60 * 60);

                if ($daysUntilExpiration < $daysThreshold) {
                    Log::warning('Certificate expiring soon', [
                        'domain' => $domain,
                        'days_until_expiration' => round($daysUntilExpiration),
                    ]);

                    try {
                        $result = $this->renewLetsEncryptCertificate($domain);
                        $renewed[] = $result;
                    } catch (Exception $e) {
                        Log::error('Failed to renew certificate', ['domain' => $domain, 'error' => $e->getMessage()]);
                    }
                }
            }

            return $renewed;
        } catch (Exception $e) {
            Log::error('Failed to check expiring certificates', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Renew specific Let's Encrypt certificate
     */
    public function renewLetsEncryptCertificate(string $domain): array
    {
        try {
            Log::info('Renewing Let\'s Encrypt certificate', ['domain' => $domain]);

            $command = sprintf(
                'certbot renew --non-interactive --cert-name %s',
                escapeshellarg($domain)
            );

            $output = [];
            $returnCode = 0;
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 && $returnCode !== 1) {
                throw new Exception('Certbot renew failed: '.implode("\n", $output));
            }

            Log::info('Successfully renewed Let\'s Encrypt certificate', ['domain' => $domain]);

            return [
                'domain' => $domain,
                'cert_path' => $this->getCertificatePath($domain),
                'key_path' => $this->getPrivateKeyPath($domain),
                'expiration' => $this->getCertificateExpiration($domain),
            ];
        } catch (Exception $e) {
            Log::error('Failed to renew certificate', ['error' => $e->getMessage(), 'domain' => $domain]);
            throw $e;
        }
    }

    /**
     * Check all certificates and alert if expiring
     */
    public function checkCertificateStatus(): array
    {
        try {
            $domains = $this->getConfiguredDomains();
            $status = [];

            foreach ($domains as $domain) {
                $expiration = $this->getCertificateExpiration($domain);
                $isValid = $this->isCertificateValid($domain);

                $expiresIn = null;
                if ($expiration) {
                    $expiresIn = ceil((strtotime($expiration) - time()) / (24 * 60 * 60));
                }

                $status[$domain] = [
                    'valid' => $isValid,
                    'expiration' => $expiration,
                    'days_until_expiration' => $expiresIn,
                    'requires_renewal' => $expiresIn && $expiresIn < 30,
                ];
            }

            return $status;
        } catch (Exception $e) {
            Log::error('Failed to check certificate status', ['error' => $e->getMessage()]);

            return [];
        }
    }

    /**
     * Get configured domains for SSL
     */
    private function getConfiguredDomains(): array
    {
        // This should pull from your domain model or configuration
        // For now, return empty array - implement based on your domain structure
        return config('services.ssl.domains') ?? [];
    }

    /**
     * Ensure directory exists for certificate storage
     */
    private function ensureDirectoryExists(string $dir): void
    {
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    /**
     * Get SSL certificate info (for dashboard display)
     */
    public function getCertificateInfo(string $domain): array
    {
        try {
            $certPath = $this->getCertificatePath($domain);

            if (! file_exists($certPath)) {
                return [
                    'status' => 'not_installed',
                    'domain' => $domain,
                ];
            }

            $cert = openssl_x509_read(file_get_contents($certPath));

            if (! $cert) {
                return [
                    'status' => 'error',
                    'domain' => $domain,
                ];
            }

            $info = openssl_x509_parse($cert);

            $expiration = $info['validTo_time_t'] ?? null;
            $isExpired = $expiration && $expiration < time();
            $isExpiringSoon = $expiration && $expiration < time() + (30 * 24 * 60 * 60);

            return [
                'status' => $isExpired ? 'expired' : ($isExpiringSoon ? 'expiring_soon' : 'valid'),
                'domain' => $domain,
                'issuer' => $info['issuer']['CN'] ?? null,
                'subject' => $info['subject']['CN'] ?? null,
                'valid_from' => date('Y-m-d H:i:s', $info['validFrom_time_t'] ?? 0),
                'valid_to' => date('Y-m-d H:i:s', $expiration ?? 0),
                'days_until_expiration' => $expiration ? ceil(($expiration - time()) / (24 * 60 * 60)) : null,
                'alt_names' => $info['extensions']['subjectAltName'] ?? null,
            ];
        } catch (Exception $e) {
            Log::error('Failed to get certificate info', ['error' => $e->getMessage(), 'domain' => $domain]);

            return [
                'status' => 'error',
                'domain' => $domain,
            ];
        }
    }
}
