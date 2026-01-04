<?php

namespace Tests\Feature;

use App\Models\BackupSchedule;
use App\Models\User;
use Tests\TestCase;

class BackupScheduleTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_backup_schedules(): void
    {
        BackupSchedule::factory(3)->for($this->user)->create();

        $response = $this->actingAs($this->user)->get('/backups/schedules');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Backups/Schedules')
            ->has('schedules', 3)
        );
    }

    public function test_user_can_create_backup_schedule(): void
    {
        $response = $this->actingAs($this->user)->post('/backups/schedules', [
            'name' => 'Weekly Backup',
            'frequency' => 'weekly',
            'time' => '02:00',
            'day_of_week' => '0',
            'backup_type' => 'full',
            'retention_days' => 30,
            'compress' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('backup_schedules', [
            'user_id' => $this->user->id,
            'name' => 'Weekly Backup',
            'frequency' => 'weekly',
        ]);
    }

    public function test_user_can_toggle_schedule(): void
    {
        $schedule = BackupSchedule::factory()->for($this->user)->create(['is_enabled' => true]);

        $response = $this->actingAs($this->user)->post("/backups/schedules/{$schedule->id}/toggle");

        $response->assertRedirect();
        $schedule->refresh();
        $this->assertFalse($schedule->is_enabled);
    }

    public function test_schedule_next_run_calculation(): void
    {
        $schedule = BackupSchedule::factory()
            ->for($this->user)
            ->create([
                'frequency' => 'daily',
                'time' => '02:00',
            ]);

        $this->assertNotNull($schedule->next_run_at);
    }

    public function test_schedule_success_rate(): void
    {
        $schedule = BackupSchedule::factory()
            ->for($this->user)
            ->create(['run_count' => 10, 'failed_count' => 2]);

        $this->assertEquals(80.0, $schedule->successRate());
    }

    public function test_user_cannot_modify_others_schedules(): void
    {
        $other = User::factory()->create();
        $schedule = BackupSchedule::factory()->for($other)->create();

        $response = $this->actingAs($this->user)->patch("/backups/schedules/{$schedule->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }
}
