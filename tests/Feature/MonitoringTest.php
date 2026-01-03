<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_access_monitoring_index(): void
    {
        $mockStats = [
            'cpu_usage' => 15.5,
            'memory' => [
                'total' => 16384,
                'used' => 8192,
                'free' => 8192,
            ],
            'disks' => [
                [
                    'name' => 'sda1',
                    'mount_point' => '/',
                    'total' => 512000,
                    'available' => 256000,
                ]
            ],
            'uptime' => 3600,
            'load_average' => [0.5, 0.6, 0.7]
        ];

        $this->mock(RustDaemonClient::class, function ($mock) use ($mockStats) {
            $mock->shouldReceive('call')
                ->with('get_system_stats')
                ->once()
                ->andReturn(['result' => $mockStats]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('monitoring.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/Index')
            ->has('stats')
            ->where('stats.cpu_usage', 15.5)
        );
    }

    public function test_user_can_fetch_monitoring_stats_json(): void
    {
        $mockStats = [
            'cpu_usage' => 20.0,
            'memory' => [
                'total' => 16384,
                'used' => 4096,
                'free' => 12288,
            ],
            'disks' => [],
            'uptime' => 7200,
            'load_average' => [1.0, 1.1, 1.2]
        ];

        $this->mock(RustDaemonClient::class, function ($mock) use ($mockStats) {
            $mock->shouldReceive('call')
                ->with('get_system_stats')
                ->once()
                ->andReturn(['result' => $mockStats]);
        });

        $response = $this->actingAs($this->user)
            ->get(route('monitoring.stats'));

        $response->assertStatus(200);
        $response->assertJson($mockStats);
    }
}
