<?php

namespace Database\Factories;

use App\Models\MonitoringAlert;
use Illuminate\Database\Eloquent\Factories\Factory;

class MonitoringAlertFactory extends Factory
{
    protected $model = MonitoringAlert::class;

    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->word()).' Alert',
            'metric' => $this->faker->randomElement(['cpu', 'memory', 'disk', 'bandwidth', 'load_average']),
            'threshold_percentage' => rand(50, 95),
            'comparison' => '>=',
            'frequency' => 'immediate',
            'notify_email' => true,
            'notify_webhook' => false,
            'is_enabled' => true,
        ];
    }

    public function disabled(): self
    {
        return $this->state(fn () => [
            'is_enabled' => false,
        ]);
    }

    public function triggered(): self
    {
        return $this->state(fn () => [
            'triggered_at' => now()->subMinutes(2),
            'consecutive_triggers' => rand(1, 3),
        ]);
    }
}
