<?php

namespace App\Services;

use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SslService
{
    use HandlesDaemonErrors;

    public function __construct(
        private RustDaemonClient $daemon
    ) {}

    /**
     * Request a Let's Encrypt certificate
     */
    public function requestLetsEncrypt(WebDomain $domain, User $user, string $validationMethod = 'http'): SslCertificate
    {
        return $this->handleDaemonCall(function () use ($domain, $user, $validationMethod) {
            // Create pending record
            $certificate = SslCertificate::create([
                'web_domain_id' => $domain->id,
                'user_id' => $user->id,
                'domain' => $domain->domain,
                'provider' => 'letsencrypt',
                'validation_method' => $validationMethod,
                'auto_renewal_enabled' => true,
                'status' => 'pending',
            ]);

            try {
                $this->daemon->requestSslCert($domain->domain, $user->email);

                // Update certificate info after successful request
                $certPath = "/etc/letsencrypt/live/{$domain->domain}/fullchain.pem";
                $keyPath = "/etc/letsencrypt/live/{$domain->domain}/privkey.pem";

                $certContent = null;
                try {
                    $certContent = $this->daemon->readFile($certPath);
                } catch (\Exception $e) {
                    if (file_exists($certPath)) {
                        $certContent = file_get_contents($certPath);
                    }
                }

                if ($certContent) {
                    $certData = openssl_x509_parse($certContent);
                    $certificate->update([
                        'status' => 'active',
                        'certificate_path' => $certPath,
                        'key_path' => $keyPath,
                        'issued_at' => $certData && isset($certData['validFrom_time_t'])
                            ? \Carbon\Carbon::createFromTimestamp($certData['validFrom_time_t'])
                            : now(),
                        'expires_at' => $certData && isset($certData['validTo_time_t'])
                            ? \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t'])
                            : now()->addDays(90),
                    ]);

                    $domain->update([
                        'has_ssl' => true,
                        'ssl_certificate_path' => $certPath,
                        'ssl_key_path' => $keyPath,
                        'ssl_expires_at' => $certificate->expires_at,
                    ]);

                    // Update vhost to use the new cert
                    $this->daemon->call('create_vhost', [
                        'domain' => $domain->domain,
                        'user' => $user->name,
                        'root' => $domain->root_path,
                        'php_version' => $domain->php_version,
                        'has_ssl' => true,
                        'ssl_certificate_path' => $certPath,
                        'ssl_key_path' => $keyPath,
                    ]);
                }

                return $certificate;
            } catch (\Exception $e) {
                $certificate->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }, "Failed to request Let's Encrypt certificate for {$domain->domain}");
    }

    /**
     * Install a custom SSL certificate
     */
    public function installCustom(WebDomain $domain, User $user, array $data): SslCertificate
    {
        return $this->handleDaemonCall(function () use ($domain, $user, $data) {
            $certContent = $data['certificate_content'];
            $keyContent = $data['key_content'];
            $caContent = $data['ca_bundle_content'] ?? null;

            $filename = $domain->domain.'_'.time();
            $certPath = 'ssl/certificates/'.$filename.'.crt';
            $keyPath = 'ssl/keys/'.$filename.'.key';
            $caPath = $caContent ? 'ssl/bundles/'.$filename.'.ca-bundle' : null;

            Storage::put($certPath, $certContent);
            Storage::put($keyPath, $keyContent);
            if ($caPath) {
                Storage::put($caPath, $caContent);
            }

            $certData = openssl_x509_parse($certContent);
            $fullCertPath = storage_path('app/'.$certPath);
            $fullKeyPath = storage_path('app/'.$keyPath);

            $certificate = SslCertificate::create([
                'web_domain_id' => $domain->id,
                'user_id' => $user->id,
                'domain' => $domain->domain,
                'provider' => 'custom',
                'status' => 'active',
                'certificate_path' => $fullCertPath,
                'key_path' => $fullKeyPath,
                'ca_bundle_path' => $caPath ? storage_path('app/'.$caPath) : null,
                'issued_at' => $certData && isset($certData['validFrom_time_t'])
                    ? \Carbon\Carbon::createFromTimestamp($certData['validFrom_time_t'])
                    : now(),
                'expires_at' => $certData && isset($certData['validTo_time_t'])
                    ? \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t'])
                    : now()->addYear(),
            ]);

            $domain->update([
                'has_ssl' => true,
                'ssl_certificate_path' => $fullCertPath,
                'ssl_key_path' => $fullKeyPath,
                'ssl_expires_at' => $certificate->expires_at,
            ]);

            // Call daemon to update vhost
            $this->daemon->call('create_vhost', [
                'domain' => $domain->domain,
                'user' => $user->name,
                'root' => $domain->root_path,
                'php_version' => $domain->php_version,
                'has_ssl' => true,
                'ssl_certificate_path' => $fullCertPath,
                'ssl_key_path' => $fullKeyPath,
            ]);

            return $certificate;
        }, "Failed to install custom certificate for {$domain->domain}");
    }

    /**
     * Renew a certificate
     */
    public function renew(SslCertificate $certificate): SslCertificate
    {
        if ($certificate->provider !== 'letsencrypt') {
            throw new Exception('Only Let\'s Encrypt certificates can be automatically renewed.');
        }

        return $this->handleDaemonCall(function () use ($certificate) {
            $certificate->update([
                'status' => 'renewing',
                'renewal_attempts' => $certificate->renewal_attempts + 1,
                'renewal_scheduled_at' => now(),
            ]);

            try {
                $this->daemon->requestSslCert($certificate->domain, $certificate->user->email);

                $certPath = $certificate->certificate_path ?: "/etc/letsencrypt/live/{$certificate->domain}/fullchain.pem";
                $certContent = null;
                try {
                    $certContent = $this->daemon->readFile($certPath);
                } catch (\Exception $e) {
                    if (file_exists($certPath)) {
                        $certContent = file_get_contents($certPath);
                    }
                }

                if ($certContent) {
                    $certData = openssl_x509_parse($certContent);
                    $certificate->update([
                        'status' => 'active',
                        'issued_at' => $certData && isset($certData['validFrom_time_t'])
                            ? \Carbon\Carbon::createFromTimestamp($certData['validFrom_time_t'])
                            : now(),
                        'expires_at' => $certData && isset($certData['validTo_time_t'])
                            ? \Carbon\Carbon::createFromTimestamp($certData['validTo_time_t'])
                            : now()->addDays(90),
                    ]);

                    if ($certificate->webDomain) {
                        $certificate->webDomain->update([
                            'ssl_expires_at' => $certificate->expires_at,
                        ]);
                    }
                }

                return $certificate;
            } catch (\Exception $e) {
                $certificate->update([
                    'status' => 'failed',
                    'last_error' => $e->getMessage(),
                ]);
                throw $e;
            }
        }, "Failed to renew certificate for {$certificate->domain}");
    }

    /**
     * Delete a certificate
     */
    public function delete(SslCertificate $certificate): bool
    {
        if ($certificate->webDomain) {
            $certificate->webDomain->update([
                'has_ssl' => false,
                'ssl_certificate_path' => null,
                'ssl_key_path' => null,
                'ssl_expires_at' => null,
            ]);

            // Update vhost to disable SSL
            try {
                $this->daemon->call('create_vhost', [
                    'domain' => $certificate->webDomain->domain,
                    'user' => $certificate->user->name,
                    'root' => $certificate->webDomain->root_path,
                    'php_version' => $certificate->webDomain->php_version,
                    'has_ssl' => false,
                ]);
            } catch (\Exception $e) {
                Log::warning('Failed to disable SSL in vhost during certificate deletion', [
                    'domain' => $certificate->domain,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $certificate->delete();
    }
}
