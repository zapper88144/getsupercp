<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailServerConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'imap_host',
        'imap_port',
        'imap_username',
        'imap_password',
        'imap_encryption',
        'from_email',
        'from_name',
        'spf_record',
        'dkim_public_key',
        'dkim_private_key',
        'dmarc_policy',
        'is_configured',
        'last_tested_at',
        'last_test_passed',
        'last_test_error',
    ];

    protected function casts(): array
    {
        return [
            'smtp_password' => 'encrypted',
            'imap_password' => 'encrypted',
            'dkim_private_key' => 'encrypted',
            'smtp_encryption' => 'boolean',
            'imap_encryption' => 'boolean',
            'is_configured' => 'boolean',
            'last_tested_at' => 'datetime',
            'last_test_passed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isHealthy(): bool
    {
        return $this->is_configured
            && $this->last_test_passed
            && ($this->last_tested_at === null || $this->last_tested_at->greaterThan(now()->subDays(7)));
    }

    public function requiresAttention(): bool
    {
        return ! $this->is_configured
            || ! $this->last_test_passed
            || ($this->last_tested_at && $this->last_tested_at->lessThan(now()->subDays(7)));
    }
}
