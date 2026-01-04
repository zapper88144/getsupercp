<?php

namespace Tests\Feature;

use App\Models\MonitoringAlert;
use App\Models\User;
use Tests\TestCase;

class MonitoringAlertTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
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
