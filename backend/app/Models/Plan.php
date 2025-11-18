<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'document_limit',
        'storage_limit',
        'max_users',
        'features',
        'is_active',
        'trial_days',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'document_limit' => 'integer',
        'storage_limit' => 'integer',
        'max_users' => 'integer',
        'features' => 'array',
        'is_active' => 'boolean',
        'trial_days' => 'integer',
    ];

    /**
     * Usuários neste plano
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
