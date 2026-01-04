<?php

namespace Tests\Feature;

use App\Models\EmailServerConfig;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EmailServerConfigTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_create_email_config(): void
    {
        $response = $this->actingAs($this->user)->post('/email', [
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => 587,
            'smtp_username' => 'user@gmail.com',
            'smtp_password' => 'password123',
            'smtp_encryption' => true,
            'from_email' => 'noreply@example.com',
            'from_name' => 'Example',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('email_server_configs', [
            'user_id' => $this->user->id,
            'smtp_host' => 'smtp.gmail.com',
            'is_configured' => true,
        ]);
    }

    public function test_user_can_view_email_config(): void
    {
        EmailServerConfig::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->get('/email');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Email/Config'));
    }

    public function test_user_can_update_email_config(): void
    {
        $config = EmailServerConfig::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->patch('/email', [
            'smtp_host' => 'smtp.sendgrid.net',
            'smtp_port' => 587,
            'smtp_username' => 'apikey',
            'from_email' => 'noreply@example.com',
            'from_name' => 'Example',
        ]);

        $response->assertRedirect();
        $config->refresh();
        $this->assertEquals('smtp.sendgrid.net', $config->smtp_host);
    }

    public function test_config_health_check(): void
    {
        $healthyConfig = EmailServerConfig::factory()
            ->for($this->user)
            ->create([
                'is_configured' => true,
                'last_test_passed' => true,
                'last_tested_at' => now()->subDays(1),
            ]);

        $this->assertTrue($healthyConfig->isHealthy());
    }

    public function test_config_requires_attention(): void
    {
        $needsAttention = EmailServerConfig::factory()
            ->for($this->user)
            ->create([
                'is_configured' => false,
            ]);

        $this->assertTrue($needsAttention->requiresAttention());
    }

    public function test_only_one_config_per_user(): void
    {
        EmailServerConfig::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->get('/email/create');

        $response->assertRedirect('/email');
    }

    public function test_email_fields_encrypted(): void
    {
        $config = EmailServerConfig::factory()->for($this->user)->create([
            'smtp_password' => 'secret123',
            'imap_password' => 'secret456',
        ]);

        // Passwords should be encrypted in database
        $raw = DB::table('email_server_configs')->find($config->id);
        $this->assertNotEquals('secret123', $raw->smtp_password);
        $this->assertNotEquals('secret456', $raw->imap_password);

        // But accessible through model
        $this->assertEquals('secret123', $config->smtp_password);
        $this->assertEquals('secret456', $config->imap_password);
    }
}
