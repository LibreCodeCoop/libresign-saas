<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = [
        'user_id',
        'plan_type',
        'amount',
        'status',
        'billing_name',
        'billing_document',
        'billing_email',
        'billing_phone',
        'billing_address',
        'billing_city',
        'billing_state',
        'billing_zip',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
