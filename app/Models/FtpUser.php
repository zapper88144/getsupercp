<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FtpUser extends Model
{
    /** @use HasFactory<\Database\Factories\FtpUserFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'username',
        'password',
        'homedir',
    ];

    protected $hidden = [
        'password',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
        ];
    }
}
