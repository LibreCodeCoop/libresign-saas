<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = [
        'name',
        'type',
        'api_key',
        'api_secret',
        'webhook_url',
        'config',
        'is_available',
    ];

    protected $casts = [
        'config' => 'array',
        'is_available' => 'boolean',
    ];

    protected $hidden = [
        'api_secret',
    ];
}
