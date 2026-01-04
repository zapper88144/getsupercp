<?php

namespace Tests\Feature;

use App\Models\SslCertificate;
use App\Models\User;
use App\Models\WebDomain;
use Tests\TestCase;

class SslCertificateTest extends TestCase
{
    private User $user;

    private WebDomain $domain;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->domain = WebDomain::factory()->for($this->user)->create();
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
        $response = $this->actingAs($this->user)->post('/ssl', [
            'web_domain_id' => $this->domain->id,
            'provider' => 'letsencrypt',
            'validation_method' => 'dns',
            'auto_renewal_enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ssl_certificates', [
            'user_id' => $this->user->id,
            'web_domain_id' => $this->domain->id,
            'status' => 'pending',
        ]);
    }

    public function test_user_can_renew_certificate(): void
    {
        $certificate = SslCertificate::factory()
            ->for($this->user)
            ->for($this->domain, 'webDomain')
            ->create(['renewal_attempts' => 0]);

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
