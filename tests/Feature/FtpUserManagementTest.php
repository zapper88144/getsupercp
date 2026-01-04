<?php

namespace Tests\Feature;

use App\Models\FtpUser;
use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FtpUserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn('success');
        });
    }

    public function test_user_can_list_ftp_users(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        FtpUser::create([
            'user_id' => $user->id,
            'username' => 'test_ftp',
            'password' => 'password123',
            'homedir' => '/home/super/web/example.com',
        ]);

        $response = $this->actingAs($user)->get('/ftp-users');

        $response->assertStatus(200);
        $response->assertSee('test_ftp');
    }

    public function test_user_can_create_ftp_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/ftp-users', [
            'username' => 'new_ftp',
            'password' => 'password123',
            'homedir' => '/home/super/web/newdomain.com',
        ]);

        $response->assertRedirect('/ftp-users');
        $this->assertDatabaseHas('ftp_users', [
            'username' => 'new_ftp',
            'user_id' => $user->id,
            'homedir' => '/home/super/web/newdomain.com',
        ]);
    }

    public function test_user_can_delete_ftp_user(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $ftpUser = FtpUser::create([
            'user_id' => $user->id,
            'username' => 'test_ftp',
            'password' => 'password123',
            'homedir' => '/home/super/web/example.com',
        ]);

        $response = $this->actingAs($user)->delete("/ftp-users/{$ftpUser->id}");

        $response->assertRedirect('/ftp-users');
        $this->assertDatabaseMissing('ftp_users', [
            'id' => $ftpUser->id,
        ]);
    }
}
