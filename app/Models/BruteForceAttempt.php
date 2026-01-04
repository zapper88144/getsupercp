<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BruteForceAttempt extends Model
{
    protected $fillable = [
        'ip_address',
        'service',
        'attempt_count',
        'first_attempt_at',
        'last_attempt_at',
        'blocked_until',
        'is_blocked',
        'username',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'attempt_count' => 'integer',
            'is_blocked' => 'boolean',
            'first_attempt_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'blocked_until' => 'datetime',
        ];
    }

    /**
     * Check if this attempt's lockout period has expired
     */
    public function isLockoutExpired(): bool
    {
        return $this->blocked_until && $this->blocked_until->isPast();
    }

    /**
     * Get attempts for a specific IP across all services
     */
    public static function forIp(string $ipAddress)
    {
        return self::where('ip_address', $ipAddress);
    }

    /**
     * Get active (non-expired) blocks
     */
    public static function activeBlocks()
    {
        return self::where('is_blocked', true)
            ->where('blocked_until', '>', now());
    }

    /**
     * Get recent attempts in the last N hours
     */
    public static function recent(int $hours = 1)
    {
        return self::where('last_attempt_at', '>', now()->subHours($hours));
    }
}
