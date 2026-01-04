<?php

namespace App\Services;

use App\Models\User;
use App\Models\WebDomain;
use App\Traits\HandlesDaemonErrors;
use Exception;
use Illuminate\Support\Facades\Log;

class WebDomainService
{
    use HandlesDaemonErrors;

    public function __construct(
        private RustDaemonClient $daemon,
        private DnsService $dnsService
    ) {}

    /**
     * Create a new web domain
     */
    public function create(User $user, array $data): WebDomain
    {
        // Validate domain format
        if (! $this->isValidDomain($data['domain'])) {
            throw new Exception('Invalid domain format');
        }

        // Check if domain already exists
        if (WebDomain::where('domain', $data['domain'])->exists()) {
            throw new Exception('Domain already exists');
        }

        return $this->handleDaemonCall(function () use ($user, $data) {
            $rootPath = $data['root_path'] ?? "/home/{$user->name}/web/{$data['domain']}/public";
            $phpVersion = $data['php_version'] ?? '8.4';

            // Create directory structure
            $this->daemon->call('create_directory', ['path' => dirname($rootPath)]);
            $this->daemon->call('create_directory', ['path' => $rootPath]);
            $this->daemon->call('create_directory', ['path' => "/home/{$user->name}/web/{$data['domain']}/logs"]);

            // Create a default index.php
            $defaultContent = "<?php\n\necho '<div style=\"font-family: sans-serif; text-align: center; padding-top: 50px;\">';\necho '<h1>Welcome to ".$data['domain']."</h1>';\necho '<p>Your website is successfully set up and hosted on SuperCP.</p>';\necho '</div>';\nphpinfo();\n";
            $this->daemon->call('write_file', [
                'path' => $rootPath.'/index.php',
                'content' => $defaultContent,
            ]);

            // Create the vhost in the Rust daemon
            $this->daemon->createVhost([
                'domain' => $data['domain'],
                'user' => $user->name,
                'root' => $rootPath,
                'php_version' => $phpVersion,
                'has_ssl' => false,
            ]);

            Log::info('Web domain vhost created', [
                'domain' => $data['domain'],
            ]);

            // Create database record
            $domain = WebDomain::create([
                'user_id' => $user->id,
                'domain' => $data['domain'],
                'root_path' => $rootPath,
                'php_version' => $phpVersion,
                'is_active' => true,
            ]);

            // Automatically create DNS zone if it doesn't exist
            try {
                $zone = \App\Models\DnsZone::where('domain', $domain->domain)->first();
                if (! $zone) {
                    $zone = $this->dnsService->createZone($user, ['domain' => $domain->domain]);
                }

                // Ensure default records exist
                $defaultIp = config('dns.default_ip', '127.0.0.1');
                $nameservers = config('dns.nameservers', ['ns1.supercp.com.', 'ns2.supercp.com.']);

                $records = [
                    ['type' => 'A', 'name' => '@', 'value' => $defaultIp],
                    ['type' => 'A', 'name' => 'www', 'value' => $defaultIp],
                ];

                foreach ($nameservers as $ns) {
                    $records[] = ['type' => 'NS', 'name' => '@', 'value' => $ns];
                }

                $this->dnsService->syncRecords($zone, $records);
            } catch (\Exception $e) {
                Log::warning('Failed to create DNS zone for web domain', [
                    'domain' => $domain->domain,
                    'error' => $e->getMessage(),
                ]);
            }

            return $domain;
        }, "Failed to create domain: {$data['domain']}");
    }

    /**
     * Update a web domain
     */
    public function update(WebDomain $domain, array $data): WebDomain
    {
        // Update in database
        $domain->update($data);

        Log::info('Web domain updated', [
            'domain' => $domain->domain,
            'data' => array_keys($data),
        ]);

        return $domain->fresh();
    }

    /**
     * Delete a web domain
     */
    public function delete(WebDomain $domain): bool
    {
        return $this->handleDaemonCall(function () use ($domain) {
            // Delete vhost from Rust daemon
            $this->daemon->deleteVhost(
                $domain->domain,
                $domain->user->name,
                $domain->php_version
            );

            Log::info('Web domain vhost deleted', [
                'domain' => $domain->domain,
            ]);

            // Delete database record
            return $domain->delete();
        }, "Failed to delete domain: {$domain->domain}");
    }

    /**
     * Toggle SSL for a domain
     */
    public function toggleSsl(WebDomain $domain, bool $enable): WebDomain
    {
        return $this->handleDaemonCall(function () use ($domain, $enable) {
            if ($enable) {
                // Request SSL certificate
                $email = config('app.admin_email', $domain->user->email);
                $this->daemon->requestSslCert($domain->domain, $email);

                $domain->update([
                    'has_ssl' => true,
                    'ssl_certificate_path' => "/etc/letsencrypt/live/{$domain->domain}/fullchain.pem",
                    'ssl_key_path' => "/etc/letsencrypt/live/{$domain->domain}/privkey.pem",
                    'ssl_expires_at' => now()->addYear(),
                ]);

                Log::info('SSL enabled for domain', ['domain' => $domain->domain]);
            } else {
                $domain->update(['has_ssl' => false]);
                Log::info('SSL disabled for domain', ['domain' => $domain->domain]);
            }

            return $domain->fresh();
        }, "Failed to toggle SSL for domain: {$domain->domain}");
    }

    /**
     * Request SSL renewal
     */
    public function renewSsl(WebDomain $domain): WebDomain
    {
        return $this->handleDaemonCall(function () use ($domain) {
            $email = config('app.admin_email', $domain->user->email);
            $this->daemon->requestSslCert($domain->domain, $email);

            $domain->update([
                'ssl_expires_at' => now()->addYear(),
            ]);

            Log::info('SSL renewed for domain', ['domain' => $domain->domain]);

            return $domain->fresh();
        }, "Failed to renew SSL for domain: {$domain->domain}");
    }

    /**
     * List all vhosts from daemon
     */
    public function listVhosts(): array
    {
        return $this->handleDaemonCall(function () {
            return $this->daemon->listVhosts();
        }, 'Failed to list vhosts from daemon', function () {
            return [];
        });
    }

    /**
     * Sync daemon vhosts with database
     */
    public function sync(): void
    {
        $this->handleDaemonCall(function () {
            $daemonVhosts = $this->listVhosts();
            $dbDomains = WebDomain::pluck('domain')->toArray();

            // Remove domains from DB that don't exist on daemon
            foreach ($dbDomains as $domain) {
                if (! in_array($domain, $daemonVhosts)) {
                    WebDomain::where('domain', $domain)->delete();
                    Log::info('Removed domain from database (not found on daemon)', [
                        'domain' => $domain,
                    ]);
                }
            }

            Log::info('Web domains synced', [
                'daemon_count' => count($daemonVhosts),
                'db_count' => WebDomain::count(),
            ]);
        }, 'Failed to sync web domains');
    }

    /**
     * Validate domain format
     */
    private function isValidDomain(string $domain): bool
    {
        // Remove trailing dot if present
        $domain = rtrim($domain, '.');

        // Check length
        if (strlen($domain) > 253) {
            return false;
        }

        // Basic domain pattern
        $pattern = '/^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i';

        return preg_match($pattern, $domain) === 1;
    }

    /**
     * Check if daemon is responding
     */
    public function isDaemonRunning(): bool
    {
        return $this->daemon->isRunning();
    }
}
