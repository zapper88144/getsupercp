<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Backup>
 */
class BackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'backup_' . $this->faker->word(),
            'type' => $this->faker->randomElement(['web', 'database']),
            'source' => $this->faker->word(),
            'path' => '/var/lib/supercp/backups/' . $this->faker->word() . '.tar.gz',
            'size' => $this->faker->numberBetween(1000, 1000000),
            'status' => 'completed',
        ];
    }
}
