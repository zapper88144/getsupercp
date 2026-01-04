<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SecurityPolicy extends Model
{
    protected $fillable = [
        'name',
        'description',
        'enable_firewall',
        'enable_brute_force_protection',
        'failed_login_threshold',
        'lockout_duration_minutes',
        'enable_ip_filtering',
        'enable_ssl_enforcement',
        'enable_cloudflare_security',
        'cloudflare_api_token',
        'security_headers',
        'waf_rules',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'enable_firewall' => 'boolean',
            'enable_brute_force_protection' => 'boolean',
            'enable_ip_filtering' => 'boolean',
            'enable_ssl_enforcement' => 'boolean',
            'enable_cloudflare_security' => 'boolean',
            'is_active' => 'boolean',
            'failed_login_threshold' => 'integer',
            'lockout_duration_minutes' => 'integer',
            'security_headers' => 'array',
            'waf_rules' => 'array',
        ];
    }

    /**
     * Get the active security policy
     */
    public static function active(): ?self
    {
        return self::where('is_active', true)->first();
    }

    /**
     * Get default security headers
     */
    public function getDefaultSecurityHeaders(): array
    {
        return [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
        ];
    }

    /**
     * Merge with default security headers if not present
     */
    public function getSecurityHeadersAttribute($value): array
    {
        $headers = $value ? json_decode($value, true) : [];

        return array_merge($this->getDefaultSecurityHeaders(), $headers);
    }
}
