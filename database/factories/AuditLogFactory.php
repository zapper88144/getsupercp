<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'action' => $this->faker->randomElement(['create', 'update', 'delete', 'view', 'login', 'logout']),
            'model' => $this->faker->randomElement(['WebDomain', 'Database', 'EmailAccount', 'FtpUser', 'DnsZone']),
            'model_id' => $this->faker->numberBetween(1, 100),
            'changes' => [
                'before' => $this->faker->word(),
                'after' => $this->faker->word(),
            ],
            'ip_address' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'result' => $this->faker->randomElement(['success', 'failed']),
            'description' => $this->faker->sentence(),
        ];
    }

    public function failed(): self
    {
        return $this->state(fn () => [
            'result' => 'failed',
        ]);
    }

    public function login(): self
    {
        return $this->state(fn () => [
            'action' => 'login',
            'model' => 'User',
        ]);
    }

    public function failedLogin(): self
    {
        return $this->state(fn () => [
            'action' => 'login',
            'result' => 'failed',
            'model' => 'User',
        ]);
    }
}
