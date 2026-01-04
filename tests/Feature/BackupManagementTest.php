<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('createBackup')->andReturn('/tmp/mock_backup.tar.gz');
            $mock->shouldReceive('createDbBackup')->andReturn('/tmp/mock_db_backup.sql.gz');
            $mock->shouldReceive('deleteFile')->andReturn('success');
            $mock->shouldReceive('restoreBackup')->andReturn('success');
            $mock->shouldReceive('restoreDbBackup')->andReturn('success');
        });
    }

    public function test_user_can_list_backups(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        Backup::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('backups.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Backups/Index')
            ->has('backups', 3)
        );
    }

    public function test_user_can_create_web_backup(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $domain = WebDomain::factory()->create(['user_id' => $user->id, 'domain' => 'example.com']);

        $response = $this->actingAs($user)->post(route('backups.store'), [
            'type' => 'web',
            'source' => 'example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backups', [
            'user_id' => $user->id,
            'type' => 'web',
            'source' => 'example.com',
            'status' => 'completed',
        ]);
    }

    public function test_user_can_delete_backup(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $backup = Backup::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('backups.destroy', $backup));

        $response->assertRedirect();
        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    public function test_user_cannot_manage_others_backups(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $backup = Backup::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->delete(route('backups.destroy', $backup));
        $response->assertStatus(403);

        $response = $this->actingAs($user2)->post(route('backups.restore', $backup));
        $response->assertStatus(403);
    }
}
