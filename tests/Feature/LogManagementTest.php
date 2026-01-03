<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LogManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_access_logs_index(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('logs.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Logs/Index')
            ->has('logTypes')
        );
    }

    public function test_user_can_fetch_logs(): void
    {
        $mockLogs = "2026-01-03 12:00:00 INFO: Daemon started\n2026-01-03 12:01:00 INFO: Connection accepted";

        $this->mock(RustDaemonClient::class, function ($mock) use ($mockLogs) {
            $mock->shouldReceive('call')
                ->with('get_logs', [
                    'type' => 'daemon',
                    'lines' => 50
                ])
                ->once()
                ->andReturn(['result' => $mockLogs]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('logs.fetch', ['type' => 'daemon', 'lines' => 50]));

        $response->assertStatus(200);
        $response->assertJson(['content' => $mockLogs]);
    }

    public function test_user_cannot_fetch_invalid_log_type(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('logs.fetch', ['type' => 'invalid_type']));

        $response->assertStatus(302); // Validation redirect
        $response->assertSessionHasErrors(['type']);
    }
}
