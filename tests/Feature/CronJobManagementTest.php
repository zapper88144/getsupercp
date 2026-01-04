<?php

namespace Tests\Feature;

use App\Models\CronJob;
use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CronJobManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the RustDaemonClient to avoid actual socket calls
        $this->mock(RustDaemonClient::class, function ($mock) {
            $mock->shouldReceive('call')->andReturn(['status' => 'success']);
            $mock->shouldReceive('updateCronJobs')->andReturn('success');
        });
    }

    public function test_user_can_list_cron_jobs(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        CronJob::factory()->count(3)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('cron-jobs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('CronJobs/Index')
            ->has('cronJobs', 3)
        );
    }

    public function test_user_can_create_cron_job(): void
    {
        /** @var User $user */
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('cron-jobs.store'), [
            'command' => 'php artisan schedule:run',
            'schedule' => '* * * * *',
            'description' => 'Run Laravel scheduler',
        ]);

        $response->assertRedirect(route('cron-jobs.index'));
        $this->assertDatabaseHas('cron_jobs', [
            'user_id' => $user->id,
            'command' => 'php artisan schedule:run',
            'schedule' => '* * * * *',
        ]);
    }

    public function test_user_can_toggle_cron_job_status(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cronJob = CronJob::factory()->create([
            'user_id' => $user->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->patch(route('cron-jobs.update', $cronJob), [
            'is_active' => false,
        ]);

        $response->assertRedirect(route('cron-jobs.index'));
        $this->assertDatabaseHas('cron_jobs', [
            'id' => $cronJob->id,
            'is_active' => false,
        ]);
    }

    public function test_user_can_delete_cron_job(): void
    {
        /** @var User $user */
        $user = User::factory()->create();
        $cronJob = CronJob::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->delete(route('cron-jobs.destroy', $cronJob));

        $response->assertRedirect(route('cron-jobs.index'));
        $this->assertDatabaseMissing('cron_jobs', ['id' => $cronJob->id]);
    }

    public function test_user_cannot_manage_others_cron_jobs(): void
    {
        /** @var User $user1 */
        $user1 = User::factory()->create();
        /** @var User $user2 */
        $user2 = User::factory()->create();
        $cronJob = CronJob::factory()->create(['user_id' => $user1->id]);

        $response = $this->actingAs($user2)->delete(route('cron-jobs.destroy', $cronJob));
        $response->assertStatus(403);

        $response = $this->actingAs($user2)->patch(route('cron-jobs.update', $cronJob), [
            'is_active' => false,
        ]);
        $response->assertStatus(403);
    }
}
