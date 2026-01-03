<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebDomain>
 */
class WebDomainFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'domain' => $this->faker->unique()->domainName(),
            'root_path' => '/var/www/html',
            'php_version' => '8.4',
            'is_active' => true,
            'has_ssl' => false,
        ];
    }
}
