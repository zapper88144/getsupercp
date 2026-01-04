<?php

namespace Database\Factories;

use App\Models\EmailServerConfig;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EmailServerConfigFactory extends Factory
{
    protected $model = EmailServerConfig::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'smtp_host' => $this->faker->domainName(),
            'smtp_port' => 587,
            'smtp_username' => $this->faker->email(),
            'smtp_password' => $this->faker->password(),
            'smtp_encryption' => true,
            'imap_host' => $this->faker->domainName(),
            'imap_port' => 993,
            'imap_username' => $this->faker->email(),
            'imap_password' => $this->faker->password(),
            'imap_encryption' => true,
            'from_email' => $this->faker->email(),
            'from_name' => $this->faker->name(),
            'spf_record' => 'v=spf1 mx -all',
            'dkim_public_key' => $this->faker->text(),
            'dkim_private_key' => $this->faker->text(),
            'dmarc_policy' => 'p=quarantine',
            'is_configured' => true,
            'last_tested_at' => now()->subDays(2),
            'last_test_passed' => true,
            'last_test_error' => null,
        ];
    }

    public function unconfigured(): self
    {
        return $this->state(fn () => [
            'is_configured' => false,
            'smtp_host' => null,
            'smtp_port' => null,
            'imap_host' => null,
            'imap_port' => null,
        ]);
    }

    public function testFailed(): self
    {
        return $this->state(fn () => [
            'last_test_passed' => false,
            'last_test_error' => 'Connection timeout',
            'last_tested_at' => now()->subDays(10),
        ]);
    }
}
