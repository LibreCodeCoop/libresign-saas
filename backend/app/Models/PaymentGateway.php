<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGateway extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'is_active',
        'sort_order',
        'settings',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'settings' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Scope para retornar apenas gateways ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope para ordenar por ordem de exibição
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc');
    }

    /**
     * Retorna gateways ativos ordenados
     */
    public static function getActiveOrdered()
    {
        return self::active()->ordered()->get();
    }
}
