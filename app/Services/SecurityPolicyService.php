<?php

namespace App\Services;

use App\Models\SecurityPolicy;
use Illuminate\Support\Facades\Log;

class SecurityPolicyService
{
    public function __construct() {}

    /**
     * Get the active security policy
     */
    public function getActivePolicy(): ?SecurityPolicy
    {
        return SecurityPolicy::where('is_active', true)->first();
    }

    /**
     * Create or update security policy
     */
    public function updatePolicy(array $data): SecurityPolicy
    {
        $policy = SecurityPolicy::where('is_active', true)->first() ?? new SecurityPolicy;
        $policy->fill($data);
        $policy->is_active = true;
        $policy->save();

        Log::info('Security policy updated', ['policy_id' => $policy->id]);

        return $policy;
    }

    /**
     * Enable or disable firewall
     */
    public function toggleFirewall(bool $enabled): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['enable_firewall' => $enabled]);

        Log::info('Firewall toggled', ['enabled' => $enabled]);

        return $policy;
    }

    /**
     * Enable or disable brute force protection
     */
    public function toggleBruteForceProtection(bool $enabled): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['enable_brute_force_protection' => $enabled]);

        Log::info('Brute force protection toggled', ['enabled' => $enabled]);

        return $policy;
    }

    /**
     * Update failed login threshold
     */
    public function setFailedLoginThreshold(int $threshold): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['failed_login_threshold' => $threshold]);

        Log::info('Failed login threshold updated', ['threshold' => $threshold]);

        return $policy;
    }

    /**
     * Update lockout duration in minutes
     */
    public function setLockoutDuration(int $minutes): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['lockout_duration_minutes' => $minutes]);

        Log::info('Lockout duration updated', ['minutes' => $minutes]);

        return $policy;
    }

    /**
     * Toggle IP filtering
     */
    public function toggleIpFiltering(bool $enabled): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['enable_ip_filtering' => $enabled]);

        Log::info('IP filtering toggled', ['enabled' => $enabled]);

        return $policy;
    }

    /**
     * Toggle SSL enforcement
     */
    public function toggleSslEnforcement(bool $enabled): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['enable_ssl_enforcement' => $enabled]);

        Log::info('SSL enforcement toggled', ['enabled' => $enabled]);

        return $policy;
    }

    /**
     * Toggle Cloudflare security
     */
    public function toggleCloudflareIntegration(bool $enabled): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['enable_cloudflare_security' => $enabled]);

        Log::info('Cloudflare integration toggled', ['enabled' => $enabled]);

        return $policy;
    }

    /**
     * Set Cloudflare API token
     */
    public function setCloudflareToken(string $token): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['cloudflare_api_token' => $token]);

        Log::info('Cloudflare API token updated');

        return $policy;
    }

    /**
     * Update security headers
     */
    public function updateSecurityHeaders(array $headers): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['security_headers' => $headers]);

        Log::info('Security headers updated');

        return $policy;
    }

    /**
     * Update WAF rules
     */
    public function updateWafRules(array $rules): SecurityPolicy
    {
        $policy = $this->getActivePolicy() ?? $this->createDefaultPolicy();
        $policy->update(['waf_rules' => $rules]);

        Log::info('WAF rules updated');

        return $policy;
    }

    /**
     * Get policy summary for dashboard
     */
    public function getPolicySummary(): array
    {
        $policy = $this->getActivePolicy();

        if (! $policy) {
            return [
                'firewall_enabled' => false,
                'brute_force_enabled' => false,
                'ssl_enforcement_enabled' => false,
                'cloudflare_enabled' => false,
                'ip_filtering_enabled' => false,
            ];
        }

        return [
            'firewall_enabled' => $policy->enable_firewall,
            'brute_force_enabled' => $policy->enable_brute_force_protection,
            'ssl_enforcement_enabled' => $policy->enable_ssl_enforcement,
            'cloudflare_enabled' => $policy->enable_cloudflare_security,
            'ip_filtering_enabled' => $policy->enable_ip_filtering,
            'failed_login_threshold' => $policy->failed_login_threshold,
            'lockout_duration_minutes' => $policy->lockout_duration_minutes,
        ];
    }

    /**
     * Create default security policy
     */
    private function createDefaultPolicy(): SecurityPolicy
    {
        return SecurityPolicy::create([
            'name' => 'default',
            'description' => 'Default security policy',
            'enable_firewall' => true,
            'enable_brute_force_protection' => true,
            'failed_login_threshold' => 5,
            'lockout_duration_minutes' => 15,
            'enable_ip_filtering' => false,
            'enable_ssl_enforcement' => true,
            'enable_cloudflare_security' => false,
            'is_active' => true,
        ]);
    }
}
