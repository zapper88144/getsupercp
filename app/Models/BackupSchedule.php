<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'frequency',
        'time',
        'day_of_week',
        'day_of_month',
        'backup_type',
        'targets',
        'retention_days',
        'compress',
        'encrypt',
        'encryption_key',
        'notify_on_completion',
        'notify_on_failure',
        'is_enabled',
        'last_run_at',
        'last_run_duration_seconds',
        'next_run_at',
        'run_count',
        'failed_count',
    ];

    protected function casts(): array
    {
        return [
            'targets' => 'json',
            'compress' => 'boolean',
            'encrypt' => 'boolean',
            'notify_on_completion' => 'boolean',
            'notify_on_failure' => 'boolean',
            'is_enabled' => 'boolean',
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot(): void
    {
        parent::boot();

        // Note: next_run_at calculation is handled in BackupScheduleController::store()
        // to ensure proper time format and frequency validation
    }

    protected function calculateNextRunAt(): ?\Illuminate\Support\Carbon
    {
        try {
            $time = $this->time ?? '02:00';
            if (empty($time)) {
                return now()->addDay();
            }

            $parts = explode(':', $time);
            if (count($parts) !== 2) {
                return now()->addDay();
            }

            $hour = (int) $parts[0];
            $minute = (int) $parts[1];

            $next = now()->setHour($hour)->setMinute($minute)->setSecond(0);

            // If that time has passed today, move to next occurrence
            if ($next <= now()) {
                $next = $next->addDay();
            }

            return match ($this->frequency) {
                'hourly' => now()->addHour(),
                'daily' => $next,
                'weekly' => $next->addWeek(),
                'monthly' => $next->addMonth(),
                default => null,
            };
        } catch (\Throwable $e) {
            return now()->addDay();
        }
    }

    public function nextRunIn(): ?string
    {
        if (! $this->next_run_at) {
            return null;
        }

        return now()->diffForHumans($this->next_run_at);
    }

    public function successRate(): float
    {
        if ($this->run_count === 0) {
            return 0;
        }

        return (($this->run_count - $this->failed_count) / $this->run_count) * 100;
    }
}
