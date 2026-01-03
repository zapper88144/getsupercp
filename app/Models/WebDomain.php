<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebDomain extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain',
        'root_path',
        'php_version',
        'is_active',
        'has_ssl',
        'ssl_certificate_path',
        'ssl_key_path',
        'ssl_expires_at',
    ];

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'has_ssl' => 'boolean',
            'ssl_expires_at' => 'datetime',
        ];
    }
}
