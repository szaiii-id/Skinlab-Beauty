<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\TwoFactorAuthenticatable;

class User extends Authenticatable 
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        // 'role',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
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
            'two_factor_confirmed_at' => 'datetime',
        ];
    }

    /**
     * Get all of the addresses for the User
     *
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get all of the orders for the User
     *
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * The wishlist that belong to the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'user_wishlist');
    }

    /**
     * Get the verification codes for the user.
     */
    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    /**
     * Get all of the whislistItems for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function whislistItems(): HasMany
    {
        return $this->hasMany(UserWhislist::class)
                    ->select(['id', 'user_id', 'product_variant_id', 'created_at']);
    }

    /**
     * Accessor for wishlist count (cached)
     */
    public function getWishlistCountAttribute(): int
    {
        return Cache::remember(
            "user_{$this->id}_wishlist_count",
            300, 
            fn() => $this->wishlistItems()->count()
        );
    }

        /**
     * Method for clear cache wishlist
     */
    public function clearWishlistCache(): void
    {
        Cache::forget("user_{$this->id}_wishlist_count");
        Cache::forget("wishlist_count_{$this->id}");
        Cache::forget("wishlist_status_{$this->id}");
        Cache::forget("user_wishlist_{$this->id}");
    }

    /**
     * Scope for eager loading wishlist count
     */
    public function scopeWithWishlistCount($query)
    {
        return $query->withCount(['wishlistItems as wishlist_count']);
    }

    /**
     * Relasi ke Membership
     * Menggunakan withDefault agar tidak error jika data belum ada (dianggap Bronze)
     */
    public function membership(): HasOne
    {
        return $this->hasOne(UserMembership::class)->withDefault([
            'tier' => 'Bronze',
            'total_spend' => 0
        ]);
    }
}
