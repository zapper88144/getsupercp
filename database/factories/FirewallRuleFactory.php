<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FirewallRule>
 */
class FirewallRuleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->word(),
            'port' => $this->faker->numberBetween(1, 65535),
            'protocol' => $this->faker->randomElement(['tcp', 'udp']),
            'action' => $this->faker->randomElement(['allow', 'deny']),
            'source' => '0.0.0.0/0',
            'is_active' => true,
        ];
    }
}
