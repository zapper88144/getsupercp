<?php

namespace Tests\Feature;

use App\Models\MonitoringAlert;
use App\Models\User;
use App\Services\RustDaemonClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class MonitoringAlertTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();

        // Mock the daemon for all tests
        $daemon = Mockery::mock(RustDaemonClient::class);
        $daemon->shouldReceive('call')->with('get_system_stats')->andReturn([
            'cpu_usage' => 50,
            'memory' => ['total' => 100, 'used' => 50],
            'disks' => [['total' => 100, 'available' => 50]],
            'load_average' => [2.5],
        ]);
        $this->app->instance(RustDaemonClient::class, $daemon);
    }

    public function test_user_can_view_monitoring_alerts(): void
    {
        MonitoringAlert::factory(3)->for($this->user)->create();

        $response = $this->actingAs($this->user)->get('/monitoring/alerts');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Monitoring/Alerts')
            ->has('alerts', 3)
        );
    }

    public function test_user_can_create_monitoring_alert(): void
    {
        $response = $this->actingAs($this->user)->post('/monitoring/alerts', [
            'name' => 'High CPU Usage',
            'metric' => 'cpu',
            'threshold_percentage' => 80,
            'comparison' => '>=',
            'frequency' => 'immediate',
            'notify_email' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('monitoring_alerts', [
            'user_id' => $this->user->id,
            'metric' => 'cpu',
            'threshold_percentage' => 80,
        ]);
    }

    public function test_user_can_toggle_alert(): void
    {
        $alert = MonitoringAlert::factory()->for($this->user)->create(['is_enabled' => true]);

        $response = $this->actingAs($this->user)->post("/monitoring/alerts/{$alert->id}/toggle");

        $response->assertRedirect();
        $alert->refresh();
        $this->assertFalse($alert->is_enabled);
    }

    public function test_user_can_update_alert(): void
    {
        $alert = MonitoringAlert::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->patch("/monitoring/alerts/{$alert->id}", [
            'name' => 'Updated Alert',
            'threshold_percentage' => 90,
            'notify_email' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('monitoring_alerts', [
            'id' => $alert->id,
            'name' => 'Updated Alert',
            'threshold_percentage' => 90,
        ]);
    }

    public function test_user_can_delete_alert(): void
    {
        $alert = MonitoringAlert::factory()->for($this->user)->create();

        $response = $this->actingAs($this->user)->delete("/monitoring/alerts/{$alert->id}");

        $response->assertRedirect();
        $this->assertModelMissing($alert);
    }

    public function test_user_cannot_update_other_users_alert(): void
    {
        $otherUser = User::factory()->create();
        $alert = MonitoringAlert::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)->patch("/monitoring/alerts/{$alert->id}", [
            'name' => 'Hacked',
        ]);

        $response->assertForbidden();
    }

    public function test_user_cannot_delete_other_users_alert(): void
    {
        $otherUser = User::factory()->create();
        $alert = MonitoringAlert::factory()->for($otherUser)->create();

        $response = $this->actingAs($this->user)->delete("/monitoring/alerts/{$alert->id}");

        $response->assertForbidden();
    }

    public function test_alert_triggered_detection(): void
    {
        $alert = MonitoringAlert::factory()
            ->for($this->user)
            ->triggered()
            ->create();

        $this->assertTrue($alert->isTriggered());
    }

    public function test_alert_not_triggered_detection(): void
    {
        $alert = MonitoringAlert::factory()
            ->for($this->user)
            ->create(['triggered_at' => null]);

        $this->assertFalse($alert->isTriggered());
    }

    public function test_various_metrics_supported(): void
    {
        $metrics = ['cpu', 'memory', 'disk', 'bandwidth', 'load_average'];

        foreach ($metrics as $metric) {
            $response = $this->actingAs($this->user)->post('/monitoring/alerts', [
                'name' => ucfirst($metric).' Alert',
                'metric' => $metric,
                'threshold_percentage' => 80,
                'comparison' => '>=',
                'frequency' => 'immediate',
                'notify_email' => true,
            ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('monitoring_alerts', [
                'metric' => $metric,
            ]);
        }
    }
}
