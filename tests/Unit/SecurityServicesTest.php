<?php

namespace Tests\Unit;

use App\Models\SecurityPolicy;
use App\Services\BruteForceService;
use App\Services\IpWhitelistService;
use App\Services\SecurityPolicyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityServicesTest extends TestCase
{
    use RefreshDatabase;

    protected SecurityPolicyService $securityPolicyService;

    protected BruteForceService $bruteForceService;

    protected IpWhitelistService $ipWhitelistService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->securityPolicyService = app()->make(SecurityPolicyService::class);
        $this->bruteForceService = app()->make(BruteForceService::class);
        $this->ipWhitelistService = app()->make(IpWhitelistService::class);
    }

    public function test_security_policy_service_creates_default_policy(): void
    {
        $policy = SecurityPolicy::create([
            'name' => 'test',
            'is_active' => true,
            'enable_firewall' => true,
        ]);

        $activePolicy = $this->securityPolicyService->getActivePolicy();

        $this->assertNotNull($activePolicy);
        $this->assertTrue($activePolicy->is_active);
    }

    public function test_security_policy_can_toggle_firewall(): void
    {
        SecurityPolicy::create([
            'name' => 'test',
            'is_active' => true,
            'enable_firewall' => true,
        ]);

        $policy = $this->securityPolicyService->toggleFirewall(false);

        $this->assertFalse($policy->enable_firewall);

        $policy = $this->securityPolicyService->toggleFirewall(true);
        $this->assertTrue($policy->enable_firewall);
    }

    public function test_brute_force_service_records_attempts(): void
    {
        // Create default policy first
        SecurityPolicy::create([
            'name' => 'test',
            'is_active' => true,
            'enable_firewall' => true,
        ]);

        $attempt = $this->bruteForceService->recordAttempt('192.168.1.1', 'ssh', 'admin');

        $this->assertEquals('192.168.1.1', $attempt->ip_address);
        $this->assertEquals('ssh', $attempt->service);
        $this->assertEquals('admin', $attempt->username);
    }

    public function test_brute_force_service_blocks_ip(): void
    {
        // Create policy with brute force enabled
        SecurityPolicy::create([
            'name' => 'test',
            'is_active' => true,
            'enable_firewall' => true,
            'enable_brute_force_protection' => true,
        ]);

        $this->bruteForceService->blockIp('192.168.1.2', 'http', 'Too many attempts');

        $isBlocked = $this->bruteForceService->isIpBlocked('192.168.1.2', 'http');

        $this->assertTrue($isBlocked);
    }

    public function test_ip_whitelist_service_adds_ip(): void
    {
        $whitelist = $this->ipWhitelistService->addIp('192.168.1.100', 'admin', 'Admin IP');

        $this->assertTrue($this->ipWhitelistService->isWhitelisted('192.168.1.100'));
    }

    public function test_ip_whitelist_removes_ip(): void
    {
        $this->ipWhitelistService->addIp('192.168.1.101', 'admin');
        $this->assertTrue($this->ipWhitelistService->isWhitelisted('192.168.1.101'));

        $this->ipWhitelistService->removeIp('192.168.1.101');
        $this->assertFalse($this->ipWhitelistService->isWhitelisted('192.168.1.101'));
    }

    public function test_whitelisted_ip_bypasses_brute_force_blocking(): void
    {
        // Create default policy
        SecurityPolicy::create([
            'name' => 'test',
            'is_active' => true,
            'enable_firewall' => true,
        ]);

        // Add IP to whitelist
        $this->ipWhitelistService->addIp('192.168.1.200', 'admin');

        // Block the same IP
        $this->bruteForceService->blockIp('192.168.1.200', 'ssh');

        // Should still be allowed because it's whitelisted
        $isBlocked = $this->bruteForceService->isIpBlocked('192.168.1.200', 'ssh');
        $this->assertFalse($isBlocked);
    }
}
