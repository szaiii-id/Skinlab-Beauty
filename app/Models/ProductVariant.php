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
    public function orderItems(): HasMany
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
        // Cek apakah ada promo aktif untuk varian ini
        // Sesuaikan 'promoBanners' dengan nama relasi Anda ke tabel promo
        $activePromo = $this->promoBanners()
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();

        if ($activePromo) {
            if ($activePromo->pivot->discount_type === 'percent') {
                return $this->price - ($this->price * ($activePromo->pivot->discount_value / 100));
            } else {
                return $this->price - $activePromo->pivot->discount_value;
            }
        }

        // Jika tidak ada promo, kembalikan null atau harga asli
        // Karena di controller Anda pakai '?? $variant->price', return null disini aman.
        return null; 
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
