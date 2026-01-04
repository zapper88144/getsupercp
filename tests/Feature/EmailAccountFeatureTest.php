<?php

namespace Tests\Feature;

use App\Models\EmailAccount;
use App\Models\User;
use Tests\TestCase;

class EmailAccountFeatureTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['is_admin' => true]);

        // Mock RustDaemonClient globally for this test
        $this->mock(\App\Services\RustDaemonClient::class, function ($mock) {
            $mock->shouldIgnoreMissing();
        });
    }

    public function test_can_list_email_accounts(): void
    {
        EmailAccount::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get('/email-accounts');

        $response->assertOk();
    }

    public function test_can_create_email_account(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/email-accounts', [
                'email' => 'newaccount@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'quota_mb' => 2048,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('email_accounts', [
            'email' => 'newaccount@example.com',
            'user_id' => $this->user->id,
            'quota_mb' => 2048,
            'status' => 'active',
        ]);
    }

    public function test_cannot_create_duplicate_email(): void
    {
        EmailAccount::factory()->create(['email' => 'existing@example.com']);

        $response = $this->actingAs($this->user)
            ->post('/email-accounts', [
                'email' => 'existing@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'quota_mb' => 2048,
            ]);

        $response->assertInvalid('email');
    }

    public function test_can_view_email_account(): void
    {
        $account = EmailAccount::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get("/email-accounts/{$account->id}");

        $response->assertOk();
    }

    public function test_can_update_email_account(): void
    {
        $account = EmailAccount::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->patch("/email-accounts/{$account->id}", [
                'quota_mb' => 4096,
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('email_accounts', [
            'id' => $account->id,
            'quota_mb' => 4096,
        ]);
    }

    public function test_can_delete_email_account(): void
    {
        $account = EmailAccount::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->delete("/email-accounts/{$account->id}");

        $response->assertRedirect();

        $this->assertSoftDeleted($account);
    }

    public function test_unauthorized_user_cannot_manage_other_accounts(): void
    {
        $otherUser = User::factory()->create();
        $account = EmailAccount::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)
            ->get("/email-accounts/{$account->id}");

        $response->assertForbidden();
    }

    public function test_validates_email_format(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/email-accounts', [
                'email' => 'invalid-email',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'quota_mb' => 2048,
            ]);

        $response->assertInvalid('email');
    }

    public function test_validates_password_strength(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/email-accounts', [
                'email' => 'test@example.com',
                'password' => 'weakpass',
                'password_confirmation' => 'weakpass',
                'quota_mb' => 2048,
            ]);

        $response->assertInvalid('password');
    }

    public function test_validates_quota_limits(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/email-accounts', [
                'email' => 'test@example.com',
                'password' => 'SecurePass123!',
                'password_confirmation' => 'SecurePass123!',
                'quota_mb' => 5, // Below minimum of 256
            ]);

        $response->assertInvalid('quota_mb');
    }

    public function test_can_check_daemon_status(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/email/daemon-status');

        $response->assertOk();
        $response->assertJsonStructure(['status', 'running']);
    }
}
