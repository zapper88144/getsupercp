<?php

namespace Tests\Feature;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BackupControllerTest extends TestCase
{
    use RefreshDatabase;

    private $user;

    private $daemonMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->daemonMock = Mockery::mock(RustDaemonClient::class);
        $this->app->instance(RustDaemonClient::class, $this->daemonMock);
    }

    public function test_index_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('backups.index'));

        $response->assertStatus(200);
    }

    public function test_can_create_manual_backup(): void
    {
        WebDomain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
        ]);

        $this->daemonMock->shouldReceive('createBackup')
            ->once()
            ->andReturn('/path/to/backup.tar.gz');

        $response = $this->actingAs($this->user)
            ->post(route('backups.store'), [
                'type' => 'web',
                'source' => 'example.com',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backups', [
            'user_id' => $this->user->id,
            'type' => 'web',
            'source' => 'example.com',
            'status' => 'completed',
        ]);
    }

    public function test_can_restore_backup(): void
    {
        $backup = Backup::factory()->create([
            'user_id' => $this->user->id,
            'type' => 'web',
            'source' => 'example.com',
            'path' => '/path/to/backup.tar.gz',
        ]);

        WebDomain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
        ]);

        $this->daemonMock->shouldReceive('restoreBackup')
            ->once()
            ->andReturn('Success');

        $response = $this->actingAs($this->user)
            ->post(route('backups.restore', $backup));

        $response->assertRedirect();
    }

    public function test_can_delete_backup(): void
    {
        $backup = Backup::factory()->create([
            'user_id' => $this->user->id,
            'path' => '/path/to/backup.tar.gz',
        ]);

        $this->daemonMock->shouldReceive('deleteFile')
            ->once()
            ->andReturn('Deleted');

        $response = $this->actingAs($this->user)
            ->delete(route('backups.destroy', $backup));

        $response->assertRedirect();
        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    public function test_can_create_backup_schedule(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('backups.schedules.store'), [
                'name' => 'Daily Backup',
                'frequency' => 'daily',
                'time' => '02:00',
                'backup_type' => 'full',
                'targets' => ['example.com'],
                'retention_days' => 30,
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backup_schedules', [
            'user_id' => $this->user->id,
            'name' => 'Daily Backup',
        ]);
    }

    public function test_can_toggle_backup_schedule(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'user_id' => $this->user->id,
            'is_enabled' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('backups.schedules.toggle', $schedule));

        $response->assertRedirect();
        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'is_enabled' => false,
        ]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
