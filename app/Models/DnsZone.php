<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DnsZone extends Model
{
    /** @use HasFactory<\Database\Factories\DnsZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'domain',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'string',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dnsRecords()
    {
        return $this->hasMany(DnsRecord::class);
    }
}
