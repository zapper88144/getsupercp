<?php

namespace Tests\Feature;

use App\Models\EmailAccount;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailAccountManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['status' => 'success']);
        });
    }

    public function test_user_can_list_email_accounts(): void
    {
        $user = User::factory()->create();
        EmailAccount::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('email-accounts.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Email/Index')
            ->has('accounts', 3)
        );
    }

    public function test_user_can_create_email_account(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);

        $response = $this->actingAs($user)->post(route('email-accounts.store'), [
            'username' => 'info',
            'domain' => 'example.com',
            'password' => 'password123',
            'quota_mb' => 500,
        ]);

        $response->assertRedirect(route('email-accounts.index'));
        $this->assertDatabaseHas('email_accounts', [
            'user_id' => $user->id,
            'email' => 'info@example.com',
            'quota_mb' => 500,
        ]);
    }

    public function test_user_cannot_create_duplicate_email_account(): void
    {
        $user = User::factory()->create();
        $domain = WebDomain::factory()->create([
            'user_id' => $user->id,
            'domain' => 'example.com',
        ]);
        EmailAccount::factory()->create([
            'user_id' => $user->id,
            'email' => 'info@example.com',
        ]);

        $response = $this->actingAs($user)->post(route('email-accounts.store'), [
            'username' => 'info',
            'domain' => 'example.com',
            'password' => 'password123',
            'quota_mb' => 500,
        ]);

        $response->assertSessionHasErrors('username');
    }

    public function test_user_can_delete_email_account(): void
    {
        $user = User::factory()->create();
        $account = EmailAccount::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('email-accounts.destroy', $account));

        $response->assertRedirect(route('email-accounts.index'));
        $this->assertDatabaseMissing('email_accounts', ['id' => $account->id]);
    }

    public function test_user_cannot_delete_others_email_account(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $account = EmailAccount::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->delete(route('email-accounts.destroy', $account));
        $response->assertStatus(403);
    }
}
