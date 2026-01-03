<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CronJob>
 */
class CronJobFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'command' => 'php artisan schedule:run',
            'schedule' => '* * * * *',
            'description' => $this->faker->sentence(),
            'is_active' => true,
        ];
    }
}
