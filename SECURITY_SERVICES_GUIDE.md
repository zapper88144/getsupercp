# Security Services Quick Reference

## SecurityPolicyService

### Basic Usage
```php
// Get active policy
$policy = app(SecurityPolicyService::class)->getActivePolicy();

// Toggle features
app(SecurityPolicyService::class)->toggleFirewall(true);
app(SecurityPolicyService::class)->toggleBruteForceProtection(true);
app(SecurityPolicyService::class)->toggleSslEnforcement(true);

// Configure thresholds
app(SecurityPolicyService::class)->setFailedLoginThreshold(5);
app(SecurityPolicyService::class)->setLockoutDuration(15); // minutes
```

### Dashboard Summary
```php
$service = app(SecurityPolicyService::class);
$summary = $service->getPolicySummary();

// Returns:
// [
//     'firewall_enabled' => true,
//     'brute_force_enabled' => true,
//     'ssl_enforcement_enabled' => true,
//     'cloudflare_enabled' => false,
//     'ip_filtering_enabled' => false,
//     'failed_login_threshold' => 5,
//     'lockout_duration_minutes' => 15,
// ]
```

## BruteForceService

### Record Failed Login Attempts
```php
$service = app(BruteForceService::class);

// Record attempt from failed login
$attempt = $service->recordAttempt(
    ipAddress: request()->ip(),
    service: 'http',
    username: 'admin'
);

// Automatically blocks if threshold exceeded
```

### Check If IP Is Blocked
```php
$service = app(BruteForceService::class);

$isBlocked = $service->isIpBlocked(
    ipAddress: request()->ip(),
    service: 'http'
);

if ($isBlocked) {
    abort(429, 'Too many requests');
}
```

### Dashboard Metrics
```php
$service = app(BruteForceService::class);
$summary = $service->getAttemptsSummary();

// Returns:
// [
//     'active_blocks' => 3,
//     'recent_attempts' => 12,
//     'top_attackers' => [
//         ['ip_address' => '192.168.1.1', 'service' => 'ssh', 'attempt_count' => 10],
//         ...
//     ],
// ]
```

### Manual IP Management
```php
$service = app(BruteForceService::class);

// Block specific IP
$service->blockIp('192.168.1.100', 'ssh', 'Manual block by admin');

// Unblock specific IP
$service->unblockIp('192.168.1.100', 'ssh');

// Get all attempts for an IP
$attempts = $service->getAttemptsForIp('192.168.1.100');
```

## IpWhitelistService

### Add IPs to Whitelist
```php
$service = app(IpWhitelistService::class);

// Permanent whitelist entry
$service->addIp('192.168.1.100', 'admin', 'Admin office');

// Temporary whitelist (expires in 24 hours)
$service->addIp('192.168.1.101', 'trusted', null, null, false, 24);

// Add CIDR range
$service->addIpRange('192.168.1.0/24', 'office-network');
```

### Cloudflare Integration
```php
$service = app(IpWhitelistService::class);

// Add all Cloudflare IP ranges
$service->addCloudflareIps();
// Adds 14 official Cloudflare IP ranges
```

### Whitelist Management
```php
$service = app(IpWhitelistService::class);

// Check if IP is whitelisted
$isWhitelisted = $service->isWhitelisted('192.168.1.100');

// Get all whitelisted IPs
$whitelist = $service->getWhitelist();

// Get by reason (cloudflare, admin, trusted)
$adminIps = $service->getByReason('admin');

// Remove from whitelist
$service->removeIp('192.168.1.100');

// Clean up expired entries
$cleared = $service->clearExpired();
```

## Integration Example: Middleware

```php
// app/Http/Middleware/CheckBruteForce.php
class CheckBruteForce
{
    public function handle(Request $request, Closure $next)
    {
        $service = app(BruteForceService::class);
        
        // Check if IP is blocked
        if ($service->isIpBlocked($request->ip(), 'http')) {
            return response('Too many requests', 429);
        }
        
        return $next($request);
    }
}
```

## Integration Example: Auth Event Listener

```php
// app/Listeners/LogFailedLogin.php
class LogFailedLogin
{
    public function handle(Failed $event)
    {
        app(BruteForceService::class)->recordAttempt(
            ipAddress: request()->ip(),
            service: 'http',
            username: $event->credentials['email'] ?? null
        );
    }
}
```

## Database Queries with Models

### Query BruteForceAttempt
```php
// Get all active blocks (not expired)
$activeBlocks = BruteForceAttempt::activeBlocks()->get();

// Get recent attempts in last hour
$recent = BruteForceAttempt::recent(1)->get();

// Get attempts for specific IP
$ipAttempts = BruteForceAttempt::forIp('192.168.1.1')->get();

// Get blocked IPs for SSH service
$sshBlocks = BruteForceAttempt::where('service', 'ssh')
    ->where('is_blocked', true)
    ->get();
```

### Query SecurityPolicy
```php
// Get active policy
$policy = SecurityPolicy::active();

// Check if feature enabled
if ($policy->enable_brute_force_protection) {
    // Apply brute-force protection
}

// Update policy
$policy->update([
    'failed_login_threshold' => 10,
    'lockout_duration_minutes' => 30,
]);
```

### Query IpWhitelist
```php
// Get all whitelisted IPs
$whitelist = IpWhitelist::all();

// Get permanent entries only
$permanent = IpWhitelist::where('is_permanent', true)->get();

// Get expired entries
$expired = IpWhitelist::where('expires_at', '<', now())->get();

// Get by reason
$cloudflare = IpWhitelist::where('reason', 'cloudflare')->get();
```

## Helper Functions (Suggested)

Add these to `app/Helpers/SecurityHelper.php`:

```php
function is_ip_blocked($ip, $service = 'http'): bool
{
    return app(BruteForceService::class)->isIpBlocked($ip, $service);
}

function is_ip_whitelisted($ip): bool
{
    return app(IpWhitelistService::class)->isWhitelisted($ip);
}

function get_security_policy(): ?SecurityPolicy
{
    return app(SecurityPolicyService::class)->getActivePolicy();
}

function record_login_attempt($ip, $username): BruteForceAttempt
{
    return app(BruteForceService::class)->recordAttempt($ip, 'http', $username);
}
```

## Configuration Examples

### Strict Security (5 attempts, 30 minute lockout)
```php
$policyService = app(SecurityPolicyService::class);
$policyService->setFailedLoginThreshold(5);
$policyService->setLockoutDuration(30);
$policyService->toggleBruteForceProtection(true);
$policyService->toggleIpFiltering(true);
$policyService->toggleSslEnforcement(true);
```

### Lenient Security (10 attempts, 15 minute lockout)
```php
$policyService = app(SecurityPolicyService::class);
$policyService->setFailedLoginThreshold(10);
$policyService->setLockoutDuration(15);
```

## Logging
All security operations are logged:
- Failed login attempts
- IP blocks/unblocks
- Whitelist changes
- Policy updates
- Expired block cleanup

Check logs at `storage/logs/laravel.log`

## Testing
Run security service tests:
```bash
php artisan test tests/Unit/SecurityServicesTest.php
```

All tests should pass with 7/7 successful.
