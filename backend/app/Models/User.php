<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'company',
        'role',
        'is_admin',
        'plan_type',
        'trial_ends_at',
        'subscription_ends_at',
        'document_limit',
        'documents_signed_this_month',
        'nextcloud_instance_id',
        'nextcloud_user_id',
        'nextcloud_status',
        'nextcloud_error',
        'nextcloud_created_at',
        'platform_url',
        // Métricas
        'storage_used_bytes',
        'storage_quota_bytes',
        'total_files',
        'last_login_at',
        'last_activity_at',
        'total_documents_signed',
        'last_document_signed_at',
        'metrics_synced_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'nextcloud_created_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_activity_at' => 'datetime',
            'last_document_signed_at' => 'datetime',
            'metrics_synced_at' => 'datetime',
        ];
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function getDaysUntilTrialEndsAttribute()
    {
        if (!$this->trial_ends_at || $this->plan_type !== 'trial') {
            return null;
        }
        
        return max(0, (int) ceil(now()->diffInDays($this->trial_ends_at, false)));
    }

    public function isOnTrial()
    {
        return $this->plan_type === 'trial' && $this->trial_ends_at && now()->lt($this->trial_ends_at);
    }

    public function nextcloudInstance()
    {
        return $this->belongsTo(NextcloudInstance::class, 'nextcloud_instance_id');
    }

    /**
     * Retorna storage usado em formato legível
     */
    public function getStorageUsedHumanAttribute(): string
    {
        return $this->formatBytes($this->storage_used_bytes);
    }

    /**
     * Retorna storage quota em formato legível
     */
    public function getStorageQuotaHumanAttribute(): string
    {
        if (!$this->storage_quota_bytes) {
            return 'Ilimitado';
        }
        return $this->formatBytes($this->storage_quota_bytes);
    }

    /**
     * Percentual de uso do storage
     */
    public function getStorageUsagePercentageAttribute(): float
    {
        if (!$this->storage_quota_bytes || $this->storage_quota_bytes == 0) {
            return 0;
        }
        return round(($this->storage_used_bytes / $this->storage_quota_bytes) * 100, 2);
    }

    /**
     * Formata bytes para formato legível
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
