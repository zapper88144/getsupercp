<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TwoFactorAuthentication extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'secret',
        'recovery_codes',
        'method',
        'phone_number',
        'is_enabled',
        'enabled_at',
        'failed_attempts',
        'last_failed_at',
    ];

    protected function casts(): array
    {
        return [
            'secret' => 'encrypted',
            'recovery_codes' => 'encrypted:array',
            'is_enabled' => 'boolean',
            'enabled_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isLocked(): bool
    {
        if ($this->failed_attempts < 3) {
            return false;
        }

        return $this->last_failed_at?->addMinutes(15)->isFuture() ?? false;
    }

    public function resetAttempts(): void
    {
        $this->update([
            'failed_attempts' => 0,
            'last_failed_at' => null,
        ]);
    }
}
