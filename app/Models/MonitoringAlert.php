<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonitoringAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'metric',
        'threshold_percentage',
        'comparison',
        'frequency',
        'notify_email',
        'notify_webhook',
        'webhook_url',
        'is_enabled',
        'triggered_at',
        'consecutive_triggers',
        'last_notification_at',
    ];

    protected function casts(): array
    {
        return [
            'notify_email' => 'boolean',
            'notify_webhook' => 'boolean',
            'is_enabled' => 'boolean',
            'triggered_at' => 'datetime',
            'last_notification_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTriggered(): bool
    {
        return $this->triggered_at !== null && $this->triggered_at->greaterThanOrEqualTo(now()->subMinutes(5));
    }

    public function timeSinceLastNotification(): ?string
    {
        if (! $this->last_notification_at) {
            return 'Never';
        }

        return $this->last_notification_at->diffForHumans();
    }
}
