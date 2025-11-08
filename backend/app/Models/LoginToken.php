<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LoginToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'expires_at',
        'used',
        'used_at',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'used' => 'boolean',
    ];

    /**
     * Relationship with User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new SSO token for a user
     * 
     * @param User $user
     * @param int $expiresInMinutes
     * @return self
     */
    public static function generateFor(User $user, int $expiresInMinutes = 5): self
    {
        // Invalidar tokens anteriores não usados
        self::where('user_id', $user->id)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->update(['used' => true, 'used_at' => now()]);

        return self::create([
            'user_id' => $user->id,
            'token' => Str::random(64),
            'expires_at' => now()->addMinutes($expiresInMinutes),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * Validate and use a token
     * 
     * @param string $token
     * @return User|null
     */
    public static function validateAndUse(string $token): ?User
    {
        $loginToken = self::where('token', $token)
            ->where('used', false)
            ->where('expires_at', '>', now())
            ->first();

        if (!$loginToken) {
            return null;
        }

        // Marcar como usado
        $loginToken->update([
            'used' => true,
            'used_at' => now(),
        ]);

        return $loginToken->user;
    }

    /**
     * Check if token is valid
     * 
     * @return bool
     */
    public function isValid(): bool
    {
        return !$this->used && $this->expires_at->isFuture();
    }

    /**
     * Clean up expired tokens (can be run in a scheduled task)
     */
    public static function cleanupExpired(): int
    {
        return self::where('expires_at', '<', now()->subHours(24))->delete();
    }
}
