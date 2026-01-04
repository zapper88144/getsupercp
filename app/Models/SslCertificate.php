<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SslCertificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'web_domain_id',
        'user_id',
        'domain',
        'provider',
        'certificate_path',
        'key_path',
        'ca_bundle_path',
        'issued_at',
        'expires_at',
        'renewal_scheduled_at',
        'auto_renewal_enabled',
        'status',
        'validation_method',
        'renewal_attempts',
        'last_error',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'renewal_scheduled_at' => 'datetime',
            'auto_renewal_enabled' => 'boolean',
        ];
    }

    public function webDomain(): BelongsTo
    {
        return $this->belongsTo(WebDomain::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function daysUntilExpiration(): ?int
    {
        if (! $this->expires_at) {
            return null;
        }

        return now()->diffInDays($this->expires_at, false);
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        $daysLeft = $this->daysUntilExpiration();

        return $daysLeft !== null && $daysLeft <= $days;
    }
}
