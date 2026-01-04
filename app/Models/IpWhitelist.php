<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IpWhitelist extends Model
{
    protected $fillable = [
        'ip_address',
        'ip_range',
        'description',
        'reason',
        'user_id',
        'is_permanent',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'is_permanent' => 'boolean',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * Get the user who added this whitelist entry
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
