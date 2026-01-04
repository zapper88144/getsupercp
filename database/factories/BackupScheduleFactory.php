<?php

namespace Database\Factories;

use App\Models\BackupSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class BackupScheduleFactory extends Factory
{
    protected $model = BackupSchedule::class;

    public function definition(): array
    {
        $time = $this->faker->time('H:i');
        
        return [
            'name' => 'Daily '.$this->faker->word().' Backup',
            'frequency' => 'daily',
            'time' => $time,
            'backup_type' => 'full',
            'targets' => ['web_domains' => [1, 2], 'databases' => [1]],
            'retention_days' => 30,
            'compress' => true,
            'encrypt' => false,
            'notify_on_completion' => true,
            'notify_on_failure' => true,
            'is_enabled' => true,
            'next_run_at' => now()->addDay(),
            'run_count' => 10,
            'failed_count' => 0,
        ];
    }

    public function weekly(): self
    {
        return $this->state(fn () => [
            'frequency' => 'weekly',
            'day_of_week' => rand(0, 6),
        ]);
    }

    public function monthly(): self
    {
        return $this->state(fn () => [
            'frequency' => 'monthly',
            'day_of_month' => rand(1, 28),
        ]);
    }
}
