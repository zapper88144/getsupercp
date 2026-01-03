<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FirewallRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'port',
        'protocol',
        'action',
        'source',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'port' => 'integer',
    ];
}
