<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory, Searchable;
    
    protected $fillable = [
        'category_id',
        'brand_id',
        'name',
        'description',
        'thumbnail',
        'slug',
        'suitability_tags', 
    ];

    protected $casts = [
        'suitability_tags' => 'array',
    ];

    public function toSearchableArray()
    {
        // Kita "Eager Load" relasi agar indexing cepat
        $this->loadMissing(['brand', 'category', 'variants']);

        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'brand_name' => $this->brand ? $this->brand->name : '',
            'category_name' => $this->category ? $this->category->name : '',
            'slug' => $this->slug,
            'thumbnail' => $this->thumbnail,
            'total_stock' => $this->variants->sum('stock'), 
            'price_min' => $this->variants->min('price'),
            'variants_sku' => $this->variants->pluck('sku')->implode(' '),
            
            'created_at' => $this->created_at,
        ];
    }
    /**
     * Get the category that owns the Product
     *
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get all of the variants for the Product
     *
     */
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Get the brand that owns the Product
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class)->where('is_hidden', false)->latest();
    }

    public function getRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1) ?? 0;
    }

    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function getThumbnailAttribute($value)
    {
        if (!$value) return null;

        if (str_contains($value, 'http')) {
            return $value;
        }

        return Storage::url($value);
    }
}
