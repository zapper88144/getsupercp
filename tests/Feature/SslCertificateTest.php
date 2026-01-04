<?php

namespace Tests\Feature;

use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Mockery;
use Tests\TestCase;

class SslCertificateTest extends TestCase
{
    private User $user;

    private WebDomain $domain;

    private $daemon;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->domain = WebDomain::factory()->for($this->user)->create();

        // Mock the RustDaemonClient
        $this->daemon = Mockery::mock(RustDaemonClient::class);
        $this->app->instance(RustDaemonClient::class, $this->daemon);
    }

    public function test_user_can_view_ssl_certificates(): void
    {
        SslCertificate::factory(3)->for($this->user)->for($this->domain, 'webDomain')->create();

        $response = $this->actingAs($this->user)->get('/ssl');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Ssl/Index')
            ->has('certificates', 3)
        );
    }

    public function test_user_can_create_ssl_certificate(): void
    {
        // Expect daemon call
        $this->daemon->shouldReceive('requestSslCert')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)->post('/ssl', [
            'web_domain_id' => $this->domain->id,
            'provider' => 'letsencrypt',
            'validation_method' => 'http',
            'auto_renewal_enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ssl_certificates', [
            'user_id' => $this->user->id,
            'web_domain_id' => $this->domain->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_upload_custom_ssl_certificate(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $certificate = \Illuminate\Http\Testing\File::create('cert.crt', 100);
        $privateKey = \Illuminate\Http\Testing\File::create('key.key', 100);

        $response = $this->actingAs($this->user)->post('/ssl', [
            'web_domain_id' => $this->domain->id,
            'provider' => 'custom',
            'input_type' => 'file',
            'certificate' => $certificate,
            'private_key' => $privateKey,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ssl_certificates', [
            'user_id' => $this->user->id,
            'web_domain_id' => $this->domain->id,
            'provider' => 'custom',
            'status' => 'active',
        ]);

        $cert = SslCertificate::where('provider', 'custom')->first();
        $this->assertNotNull($cert->certificate_path);
        $this->assertNotNull($cert->key_path);
    }

    public function test_user_can_create_custom_ssl_certificate_via_text(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');

        $response = $this->actingAs($this->user)->post('/ssl', [
            'web_domain_id' => $this->domain->id,
            'provider' => 'custom',
            'input_type' => 'text',
            'certificate_text' => "-----BEGIN CERTIFICATE-----\nMIID...\n-----END CERTIFICATE-----",
            'private_key_text' => "-----BEGIN PRIVATE KEY-----\nMIIE...\n-----END PRIVATE KEY-----",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ssl_certificates', [
            'user_id' => $this->user->id,
            'web_domain_id' => $this->domain->id,
            'provider' => 'custom',
            'status' => 'active',
        ]);

        $cert = SslCertificate::where('provider', 'custom')->latest()->first();
        $this->assertNotNull($cert->certificate_path);
        $this->assertNotNull($cert->key_path);
        $this->assertStringContainsString('ssl/certificates/', $cert->certificate_path);
        $this->assertStringContainsString('ssl/keys/', $cert->key_path);
    }

    public function test_user_can_renew_certificate(): void
    {
        $certificate = SslCertificate::factory()
            ->for($this->user)
            ->for($this->domain, 'webDomain')
            ->create(['renewal_attempts' => 0]);

        // Expect daemon call
        $this->daemon->shouldReceive('requestSslCert')
            ->once()
            ->andReturn(true);

        $response = $this->actingAs($this->user)->post("/ssl/{$certificate->id}/renew");

        $response->assertRedirect();
        $certificate->refresh();
        $this->assertEquals(1, $certificate->renewal_attempts);
        $this->assertEquals('renewing', $certificate->status);
    }

    public function test_user_cannot_view_others_certificates(): void
    {
        $other = User::factory()->create();
        $certificate = SslCertificate::factory()
            ->for($other)
            ->for($this->domain, 'webDomain')
            ->create();

        $response = $this->actingAs($this->user)->get("/ssl/{$certificate->id}");

        $response->assertForbidden();
    }

    public function test_certificate_expiration_check(): void
    {
        $certificate = SslCertificate::factory()
            ->for($this->user)
            ->for($this->domain, 'webDomain')
            ->expiringSoon()
            ->create();

        $this->assertTrue($certificate->isExpiringSoon(30));
        // Allow 14-15 days due to timing variations in diffInDays
        $daysLeft = $certificate->daysUntilExpiration();
        $this->assertGreaterThanOrEqual(14, $daysLeft);
        $this->assertLessThanOrEqual(15, $daysLeft);
    }

    public function test_expired_certificate_status(): void
    {
        $certificate = SslCertificate::factory()
            ->for($this->user)
            ->for($this->domain, 'webDomain')
            ->expired()
            ->create();

        $this->assertTrue($certificate->daysUntilExpiration() < 0);
        $this->assertEquals('expired', $certificate->status);
    }
}
