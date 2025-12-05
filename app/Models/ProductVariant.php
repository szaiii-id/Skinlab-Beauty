<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'volume',
        'color_shade',
        'price',
        'stock',
        'sku',
        'image_url'
    ];

    protected $appends = [
        'final_price',
        'discount_info',
        'image'
    ];

    /**
     * Get the product that owns the ProductVariant
     *
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get all of the orderItens for the ProductVariant
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderItens(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function promoBanners()
    {
        return $this->belongsToMany(PromoBanner::class, 'product_promo_banner', 'product_variant_id', 'promo_banner_id')
                    ->withPivot(['discount_type', 'discount_value'])
                    ->withTimestamps();
    }

    // --- 1. ACCESSOR GAMBAR ASLI (image_url) ---
    public function getImageUrlAttribute($value)
    {
        if (!$value) return null;
        if (str_contains($value, 'http')) return $value;
        return Storage::url($value);
    }

    // --- 2. ACCESSOR ALIAS (image) - PERBAIKAN ERROR ---
    // Ini wajib ada karena 'image' terdaftar di $appends
    public function getImageAttribute()
    {
        return $this->image_url; // Ambil value dari accessor di atas
    }

    // --- 3. LOGIC HARGA CORET ---
    public function getFinalPriceAttribute()
    {
        // Cari banner yang LIVE
        $activePromo = $this->promoBanners->first(function ($banner) {
            return $banner->is_live; 
        });

        if ($activePromo) {
            $type = $activePromo->pivot->discount_type;
            $val  = $activePromo->pivot->discount_value;
            
            $cutAmount = ($type === 'percent') ? ($this->price * $val / 100) : $val;
            
            return max(0, $this->price - $cutAmount);
        }

        return $this->price;
    }
    
    public function getDiscountInfoAttribute()
    {
        $activePromo = $this->promoBanners->first(fn($b) => $b->is_live);
        if (!$activePromo) return null;

        return [
            'type' => $activePromo->pivot->discount_type,
            'value' => $activePromo->pivot->discount_value
        ];
    }
}
