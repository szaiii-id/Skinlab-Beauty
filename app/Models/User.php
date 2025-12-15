<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Scout\Searchable;

class User extends Authenticatable 
{
    use HasFactory, Notifiable, TwoFactorAuthenticatable, Searchable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'current_points', 
        'is_banned',
        'banned_at',
        'ban_reason',
        'banned_by',
    ];

    protected $hidden = [
        'password',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_banned' => 'boolean',
            'current_points' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * Searchable configuration
     */
    public function searchableAs(): string
    {
        return 'users_index_v1';
    }

    public function shouldBeSearchable(): bool
    {
        return !$this->is_banned && $this->email_verified_at !== null;
    }

    public function toSearchableArray(): array
    {
        // PERBAIKAN: Gunakan order_status bukan status
        $this->loadMissing([
            'orders' => function ($query) {
                $query->select('user_id', 'order_status', 'total_amount', 'created_at') // PERBAIKAN
                      ->where('order_status', 'completed'); // PERBAIKAN
            }
        ]);
        
        $lastOrder = $this->orders->sortByDesc('created_at')->first();
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at ? $this->created_at->timestamp : 0,
            'total_spend' => (int) $this->orders->sum('total_amount'),
            'order_count' => $this->orders->count(),
            'last_order_date' => $lastOrder ? $lastOrder->created_at->timestamp : 0,
            'is_banned' => (bool) $this->is_banned,
            'has_verified_email' => !is_null($this->email_verified_at),
            'current_points' => (int) $this->current_points,
        ];
    }

    /**
     * Accessor for total spend - PERBAIKAN
     */
    public function getTotalSpendAttribute(): int
    {
        return Cache::remember(
            "user_{$this->id}_total_spend",
            3600,
            fn() => $this->orders()->where('order_status', 'completed')->sum('total_amount') ?? 0 // PERBAIKAN
        );
    }

    /**
     * Accessor for order count
     */
    public function getOrderCountAttribute(): int
    {
        return Cache::remember(
            "user_{$this->id}_order_count",
            3600,
            fn() => $this->orders()->count()
        );
    }

    /**
     * Accessor for last order date
     */
    public function getLastOrderDateAttribute()
    {
        return Cache::remember(
            "user_{$this->id}_last_order",
            3600,
            fn() => $this->orders()->latest()->first()?->created_at
        );
    }

    /**
     * Check if user is sleeping (no order in 90 days)
     */
    public function getIsSleepingAttribute(): bool
    {
        $lastOrder = $this->last_order_date;
        if (!$lastOrder) {
            return true;
        }
        
        return $lastOrder->diffInDays(now()) > 90;
    }

    /**
     * Check if user is loyal (spent > 2M or orders > 5)
     */
    public function getIsLoyalAttribute(): bool
    {
        return $this->total_spend >= 2000000 || $this->order_count >= 5;
    }

    /**
     * Get all of the addresses for the User
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(UserAddress::class);
    }

    /**
     * Get all of the orders for the User
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * Get completed orders - PERBAIKAN
     */
    public function completedOrders(): HasMany
    {
        return $this->orders()->where('order_status', 'completed'); // PERBAIKAN
    }

    /**
     * Get the latest order
     */
    public function latestOrder(): HasOne
    {
        return $this->hasOne(Order::class)->latestOfMany();
    }

    /**
     * The wishlist that belong to the User
     */
    public function wishlist(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'user_wishlist')
                    ->withTimestamps();
    }

    /**
     * Get the verification codes for the user.
     */
    public function verificationCodes(): HasMany
    {
        return $this->hasMany(VerificationCode::class);
    }

    /**
     * Get wishlist items count (cached)
     */
    public function getWishlistCountAttribute(): int
    {
        return Cache::remember(
            "user_{$this->id}_wishlist_count",
            300,
            fn() => $this->wishlist()->count()
        );
    }

    /**
     * Clear wishlist cache
     */
    public function clearWishlistCache(): void
    {
        Cache::forget("user_{$this->id}_wishlist_count");
    }

    /**
     * Clear all user-related cache
     */
    public function clearCache(): void
    {
        $this->clearWishlistCache();
        Cache::forget("user_{$this->id}_total_spend");
        Cache::forget("user_{$this->id}_order_count");
        Cache::forget("user_{$this->id}_last_order");
    }

    /**
     * Scope for eager loading wishlist count
     */
    public function scopeWithWishlistCount($query)
    {
        return $query->withCount('wishlist as wishlist_count');
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_banned', false)
                    ->whereNotNull('email_verified_at');
    }

    /**
     * Scope for sleeping users (no order in 90 days)
     */
    public function scopeSleeping($query)
    {
        return $query->whereDoesntHave('orders', function ($q) {
            $q->where('created_at', '>=', now()->subDays(90));
        });
    }

    /**
     * Scope for loyal users - PERBAIKAN
     */
    public function scopeLoyal($query)
    {
        return $query->whereHas('completedOrders', function ($q) {
            $q->selectRaw('user_id, SUM(total_amount) as total_spend, COUNT(*) as order_count')
              ->groupBy('user_id')
              ->havingRaw('SUM(total_amount) >= ? OR COUNT(*) >= ?', [2000000, 5]);
        });
    }

    public function scopeFirstTimeBuyers($query)
    {
        return $query->whereHas('orders', function ($q) {
            $q->select('user_id')
            ->where('order_status', 'completed')
            ->groupBy('user_id')
            ->havingRaw('COUNT(*) = 1')
            ->havingRaw('MAX(created_at) >= ?', [now()->subDays(30)]);
        });
    }

    /**
     * Relasi ke Membership
     */
    public function membership(): HasOne
    {
        return $this->hasOne(UserMembership::class)->withDefault([
            'tier' => 'Bronze',
            'total_spend' => 0,
            'benefits' => []
        ]);
    }

    /**
     * Boot the model
     */
    protected static function booted(): void
    {
        static::updated(function ($user) {
            $user->clearCache();
        });

        static::deleted(function ($user) {
            $user->clearCache();
        });
    }

    /**
     * Admin yang melakukan ban
     */
    public function bannedBy(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'banned_by');
    }
    
    /**
     * Get all ban requests for this user
     */
    public function banRequests(): HasMany
    {
        return $this->hasMany(BanRequest::class);
    }
    
    /**
     * Check if user has pending ban request
     */
    public function hasPendingBanRequest(): bool
    {
        return $this->banRequests()
            ->where('status', BanRequest::STATUS_PENDING)
            ->exists();
    }
    
    /**
     * Ban this user
     */
    public function ban(string $reason, ?Admin $admin = null): bool
    {
        $this->update([
            'is_banned' => true,
            'banned_at' => now(),
            'ban_reason' => $reason,
            'banned_by' => $admin?->id
        ]);
        
        // Clear cache jika perlu
        $this->clearCache();
        
        return true;
    }
    
    /**
     * Unban this user
     */
    public function unban(): bool
    {
        $this->update([
            'is_banned' => false,
            'banned_at' => null,
            'ban_reason' => null,
            'banned_by' => null
        ]);
        
        $this->clearCache();
        
        return true;
    }
    
    /**
     * Check if user can be banned
     */
    public function canBeBanned(): bool
    {
        return !$this->is_banned && !$this->hasPendingBanRequest();
    }

    public function fcm_tokens() 
    {
        return $this->hasMany(FcmToken::class); 
    }

    /**
     * [PERBAIKAN] Mengambil token dari RELASI
     */
    public function routeNotificationForFcm($notification = null)
    {
        return FcmToken::where('user_id', $this->id)->pluck('token')->toArray();
    }
}