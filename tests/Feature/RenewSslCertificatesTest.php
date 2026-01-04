<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RenewSslCertificatesTest extends TestCase
{
    use RefreshDatabase;

    protected $daemon;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the RustDaemonClient
        $this->daemon = Mockery::mock(RustDaemonClient::class);
        $this->app->instance(RustDaemonClient::class, $this->daemon);
    }

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

        // Expect the daemon to be called
        $this->daemon->shouldReceive('requestSslCert')
            ->once()
            ->with('example.com', $user->email)
            ->andReturn(true);

        $this->daemon->shouldReceive('readFile')
            ->andReturn('');

        $this->artisan('app:renew-ssl-certificates')
            ->assertExitCode(0);
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

        // Expect the daemon to be called even with valid certificate
        $this->daemon->shouldReceive('requestSslCert')
            ->once()
            ->with('example.com', $user->email)
            ->andReturn(true);

        $this->daemon->shouldReceive('readFile')
            ->andReturn('');

        $this->artisan('app:renew-ssl-certificates', ['--force' => true])
            ->assertExitCode(0);
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

        // Expect only example.com to be renewed
        $this->daemon->shouldReceive('requestSslCert')
            ->once()
            ->with('example.com', $user->email)
            ->andReturn(true);

        $this->daemon->shouldReceive('readFile')
            ->andReturn('');

        $this->artisan('app:renew-ssl-certificates', ['--domain' => 'example.com'])
            ->assertExitCode(0);
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

        // Mock daemon to fail
        $this->daemon->shouldReceive('requestSslCert')
            ->once()
            ->andThrow(new \Exception('Certificate renewal failed'));

        $this->artisan('app:renew-ssl-certificates')
            ->assertExitCode(0);
    }
}
