<?php

namespace Tests\Unit;

use App\Models\MonitoringAlert;
use App\Models\User;
use App\Services\MonitoringService;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MonitoringServiceTest extends TestCase
{
    use RefreshDatabase;

    protected MonitoringService $service;

    protected MockInterface $daemon;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var RustDaemonClient&MockInterface */
        $this->daemon = $this->mock(RustDaemonClient::class);
        $this->service = new MonitoringService($this->daemon);
    }

    public function test_can_get_system_metrics(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('get_system_stats')
            ->once()
            ->andReturn([
                'cpu_usage' => 45.5,
                'memory' => [
                    'total' => 1000,
                    'used' => 623,
                ],
                'disks' => [
                    [
                        'total' => 1000,
                        'available' => 211,
                    ],
                ],
                'load_average' => [2.5, 2.0, 1.5],
            ]);

        $metrics = $this->service->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertEquals(45.5, $metrics['cpu_percentage']);
        $this->assertEquals(62.3, $metrics['memory_percentage']);
        $this->assertEquals(78.9, $metrics['disk_percentage']);
    }

    public function test_get_metrics_handles_invalid_response(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('get_system_stats')
            ->once()
            ->andReturn([]);

        $metrics = $this->service->getMetrics();

        $this->assertIsArray($metrics);
        $this->assertEquals(0, $metrics['cpu_percentage']);
    }

    public function test_can_check_threshold_greater_than(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('get_system_stats')
            ->andReturn([
                'cpu_usage' => 85,
                'memory' => ['total' => 100, 'used' => 50],
                'disks' => [['total' => 100, 'available' => 50]],
                'load_average' => [2.5],
            ]);

        $user = User::factory()->create();
        $alert = MonitoringAlert::factory()->for($user)->create([
            'metric' => 'cpu',
            'comparison' => '>',
            'threshold_percentage' => 80,
        ]);

        $result = $this->service->checkThresholds($alert);

        $this->assertTrue($result);
    }

    public function test_can_check_threshold_greater_equal(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('get_system_stats')
            ->andReturn([
                'cpu_usage' => 80,
                'memory' => ['total' => 100, 'used' => 50],
                'disks' => [['total' => 100, 'available' => 50]],
                'load_average' => [2.5],
            ]);

        $user = User::factory()->create();
        $alert = MonitoringAlert::factory()->for($user)->create([
            'metric' => 'cpu',
            'comparison' => '>=',
            'threshold_percentage' => 80,
        ]);

        $result = $this->service->checkThresholds($alert);

        $this->assertTrue($result);
    }

    public function test_can_check_threshold_less_than(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('get_system_stats')
            ->andReturn([
                'cpu_usage' => 30,
                'memory' => ['total' => 100, 'used' => 50],
                'disks' => [['total' => 100, 'available' => 50]],
                'load_average' => [2.5],
            ]);

        $user = User::factory()->create();
        $alert = MonitoringAlert::factory()->for($user)->create([
            'metric' => 'cpu',
            'comparison' => '<',
            'threshold_percentage' => 50,
        ]);

        $result = $this->service->checkThresholds($alert);

        $this->assertTrue($result);
    }

    public function test_daemon_health_check(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('ping')
            ->once()
            ->andReturn('pong');

        $result = $this->service->isDaemonRunning();

        $this->assertTrue($result);
    }

    public function test_daemon_health_check_failure(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('ping')
            ->once()
            ->andThrow(new \Exception('Connection failed'));

        $result = $this->service->isDaemonRunning();

        $this->assertFalse($result);
    }

    public function test_metrics_defaults_on_exception(): void
    {
        $this->daemon->shouldReceive('call')
            ->with('get_system_stats')
            ->andThrow(new \Exception('Daemon error'));

        $metrics = $this->service->getMetrics();

        $this->assertEquals(0, $metrics['cpu_percentage']);
        $this->assertEquals(0, $metrics['memory_percentage']);
    }
}
