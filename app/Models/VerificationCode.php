<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCode extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code',
        'attempts',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];

    /**
     * Get the user that owns the VerificationCode
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function incrementAttempts(): bool
    {
        return $this->increment('attempts');
    }

    public static function cleanupExpired(): void
    {
        static::where('expires_at', '<', now())->delete();
    }
}
