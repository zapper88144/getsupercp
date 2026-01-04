<?php

namespace Tests\Unit;

use App\Models\Backup;
use App\Models\BackupSchedule;
use App\Models\User;
use App\Models\WebDomain;
use App\Services\BackupService;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BackupServiceTest extends TestCase
{
    use RefreshDatabase;

    private $daemonMock;

    private $backupService;

    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->daemonMock = Mockery::mock(RustDaemonClient::class);
        $this->backupService = new BackupService($this->daemonMock);
        $this->user = User::factory()->create();
    }

    public function test_can_create_web_backup(): void
    {
        $domain = WebDomain::factory()->create([
            'user_id' => $this->user->id,
            'domain' => 'example.com',
            'root_path' => '/var/www/example.com',
        ]);

        $this->daemonMock->shouldReceive('createBackup')
            ->once()
            ->with(Mockery::pattern('/^backup_web_example\.com_/'), '/var/www/example.com')
            ->andReturn('/var/lib/supercp/backups/backup_web_example.com_test.tar.gz');

        $backup = $this->backupService->createBackup($this->user, 'web', 'example.com');

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals('web', $backup->type);
        $this->assertEquals('example.com', $backup->source);
        $this->assertEquals('completed', $backup->status);
        $this->assertEquals('/var/lib/supercp/backups/backup_web_example.com_test.tar.gz', $backup->path);
    }

    public function test_can_create_database_backup(): void
    {
        $this->daemonMock->shouldReceive('createDbBackup')
            ->once()
            ->with('test_db')
            ->andReturn('/var/lib/supercp/backups/backup_db_test_db_test.tar.gz');

        $backup = $this->backupService->createBackup($this->user, 'database', 'test_db');

        $this->assertInstanceOf(Backup::class, $backup);
        $this->assertEquals('database', $backup->type);
        $this->assertEquals('test_db', $backup->source);
        $this->assertEquals('completed', $backup->status);
    }

    public function test_backup_creation_failure_updates_status(): void
    {
        $this->daemonMock->shouldReceive('createDbBackup')
            ->once()
            ->andThrow(new \Exception('Daemon error'));

        try {
            $this->backupService->createBackup($this->user, 'database', 'test_db');
        } catch (\Exception $e) {
            $this->assertEquals('Failed to create backup: Daemon error', $e->getMessage());
        }

        $backup = Backup::where('source', 'test_db')->first();
        $this->assertEquals('failed', $backup->status);
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
            'root_path' => '/var/www/example.com',
        ]);

        $this->daemonMock->shouldReceive('restoreBackup')
            ->once()
            ->with('/path/to/backup.tar.gz', '/var/www/example.com')
            ->andReturn('Success');

        $result = $this->backupService->restore($backup);

        $this->assertTrue($result);
    }

    public function test_can_delete_backup(): void
    {
        $backup = Backup::factory()->create([
            'user_id' => $this->user->id,
            'path' => '/path/to/backup.tar.gz',
        ]);

        $this->daemonMock->shouldReceive('deleteFile')
            ->once()
            ->with('/path/to/backup.tar.gz')
            ->andReturn('Deleted');

        $result = $this->backupService->delete($backup);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('backups', ['id' => $backup->id]);
    }

    public function test_can_create_schedule(): void
    {
        $data = [
            'name' => 'Daily Web Backup',
            'frequency' => 'daily',
            'time' => '03:00',
            'backup_type' => 'files_only',
            'targets' => ['example.com'],
        ];

        $schedule = $this->backupService->createSchedule($this->user, $data);

        $this->assertInstanceOf(BackupSchedule::class, $schedule);
        $this->assertEquals('Daily Web Backup', $schedule->name);
        $this->assertEquals('daily', $schedule->frequency);
        $this->assertEquals('03:00', $schedule->time);
    }

    public function test_can_update_schedule(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Old Name',
        ]);

        $updated = $this->backupService->updateSchedule($schedule, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('backup_schedules', [
            'id' => $schedule->id,
            'name' => 'New Name',
        ]);
    }

    public function test_can_delete_schedule(): void
    {
        $schedule = BackupSchedule::factory()->create([
            'user_id' => $this->user->id,
        ]);

        $result = $this->backupService->deleteSchedule($schedule);

        $this->assertTrue($result);
        $this->assertDatabaseMissing('backup_schedules', ['id' => $schedule->id]);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
