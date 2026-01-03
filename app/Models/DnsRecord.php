<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DnsRecord extends Model
{
    /** @use HasFactory<\Database\Factories\DnsRecordFactory> */
    use HasFactory;

    protected $fillable = [
        'dns_zone_id',
        'type',
        'name',
        'value',
        'priority',
        'ttl',
    ];

    public function dnsZone()
    {
        return $this->belongsTo(DnsZone::class);
    }
}
