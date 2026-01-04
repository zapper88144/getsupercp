<?php

namespace Database\Factories;

use App\Models\SslCertificate;
use App\Models\WebDomain;
use Illuminate\Database\Eloquent\Factories\Factory;

class SslCertificateFactory extends Factory
{
    protected $model = SslCertificate::class;

    public function definition(): array
    {
        return [
            'web_domain_id' => WebDomain::factory(),
            'domain' => $this->faker->domainName(),
            'provider' => 'letsencrypt',
            'certificate_path' => '/etc/ssl/certs/'.$this->faker->slug().'.crt',
            'key_path' => '/etc/ssl/private/'.$this->faker->slug().'.key',
            'issued_at' => now()->subDays(30),
            'expires_at' => now()->addDays(60),
            'auto_renewal_enabled' => true,
            'status' => 'active',
            'validation_method' => 'dns',
        ];
    }

    public function expired(): self
    {
        return $this->state(fn () => [
            'expires_at' => now()->subDays(5),
            'status' => 'expired',
        ]);
    }

    public function expiringSoon(): self
    {
        return $this->state(fn () => [
            'expires_at' => now()->addDays(15),
            'status' => 'active',
        ]);
    }
}
