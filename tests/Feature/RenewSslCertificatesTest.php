<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebDomain;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class RenewSslCertificatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_skips_domains_without_ssl(): void
    {
        $user = User::factory()->create();
        WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => false,
        ]);

        $this->artisan('app:renew-ssl-certificates')
            ->expectsOutput('No SSL domains found to renew.')
            ->assertExitCode(0);
    }

    public function test_command_skips_valid_certificates(): void
    {
        $user = User::factory()->create();
        WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => true,
            'ssl_expires_at' => now()->addDays(60),
        ]);

        $this->artisan('app:renew-ssl-certificates')
            ->assertExitCode(0);
    }

    public function test_command_renews_expiring_certificates(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => true,
            'domain' => 'example.com',
            'ssl_expires_at' => now()->addDays(15),
        ]);

        // Mock the Process to avoid actual certbot calls
        Process::fake([
            'certbot renew --cert-name example.com *' => Process::result('', exitCode: 0),
            'systemctl reload nginx *' => Process::result('', exitCode: 0),
        ]);

        $this->artisan('app:renew-ssl-certificates')
            ->assertExitCode(0);

        // Verify process was called
        Process::assertRan('certbot renew --cert-name example.com --non-interactive --agree-tos --no-eff-email 2>&1');
    }

    public function test_command_with_force_option(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => true,
            'domain' => 'example.com',
            'ssl_expires_at' => now()->addDays(60),
        ]);

        // Mock the Process to avoid actual certbot calls
        Process::fake([
            'certbot renew --cert-name example.com *' => Process::result('', exitCode: 0),
            'systemctl reload nginx *' => Process::result('', exitCode: 0),
        ]);

        $this->artisan('app:renew-ssl-certificates', ['--force' => true])
            ->assertExitCode(0);

        // Verify process was called even with valid certificate
        Process::assertRan('certbot renew --cert-name example.com --non-interactive --agree-tos --no-eff-email 2>&1');
    }

    public function test_command_with_specific_domain(): void
    {
        $user = User::factory()->create();
        WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => true,
            'domain' => 'example.com',
            'ssl_expires_at' => now()->addDays(15),
        ]);
        WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => true,
            'domain' => 'other.com',
            'ssl_expires_at' => now()->addDays(15),
        ]);

        // Mock the Process
        Process::fake([
            'certbot renew --cert-name example.com *' => Process::result('', exitCode: 0),
            'systemctl reload nginx *' => Process::result('', exitCode: 0),
        ]);

        $this->artisan('app:renew-ssl-certificates', ['--domain' => 'example.com'])
            ->assertExitCode(0);

        Process::assertRan('certbot renew --cert-name example.com --non-interactive --agree-tos --no-eff-email 2>&1');
    }

    public function test_command_handles_renewal_failure(): void
    {
        $user = User::factory()->create();
        WebDomain::factory()->create([
            'user_id' => $user->id,
            'has_ssl' => true,
            'domain' => 'example.com',
            'ssl_expires_at' => now()->addDays(15),
        ]);

        // Mock certbot to fail
        Process::fake([
            'certbot renew --cert-name example.com *' => Process::result('Certificate renewal failed', exitCode: 1),
        ]);

        $this->artisan('app:renew-ssl-certificates')
            ->assertExitCode(0);

        // Command should complete successfully but report the failure
    }
}
