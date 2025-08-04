<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserVerification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'token',
        'type',
        'expires_at',
        'verified_at',
        'is_used',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    /**
     * Get the user that owns the verification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Generate a new verification token.
     */
    public static function generateToken(): string
    {
        return Str::random(64);
    }

    /**
     * Create a new verification for a user.
     */
    public static function createForUser(User $user, string $type = 'email', int $expiresInHours = 24): self
    {
        return self::create([
            'user_id' => $user->id,
            'token' => self::generateToken(),
            'type' => $type,
            'expires_at' => now()->addHours($expiresInHours),
        ]);
    }

    /**
     * Check if the verification is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if the verification is valid.
     */
    public function isValid(): bool
    {
        return !$this->is_used && !$this->isExpired();
    }

    /**
     * Mark the verification as used.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'is_used' => true,
            'verified_at' => now(),
        ]);
    }

    /**
     * Scope to filter valid verifications.
     */
    public function scopeValid($query)
    {
        return $query->where('is_used', false)
                    ->where('expires_at', '>', now());
    }

    /**
     * Scope to filter by type.
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Find a valid verification by token.
     */
    public static function findValidByToken(string $token, string $type = 'email'): ?self
    {
        return self::where('token', $token)
                   ->byType($type)
                   ->valid()
                   ->first();
    }
}

