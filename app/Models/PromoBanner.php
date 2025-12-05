<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class PromoBanner extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'title',
        'subtitle',
        'image_url',
        'is_active',
        'start_date',
        'end_date'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
    ];

    protected $appends = [
        'image', 
        'status_label', 
        'is_live'
    ];

    public function variants()
    {
        return $this->belongsToMany(ProductVariant::class, 'product_promo_banner', 'promo_banner_id', 'product_variant_id')
                    ->withPivot(['discount_type', 'discount_value'])
                    ->withTimestamps();
    }

    public function getImageAttribute()
    {
        if (!$this->image_url) return null;
        if (str_contains($this->image_url, 'http')) return $this->image_url;
        return Storage::url($this->image_url);
    }

    public function getIsLiveAttribute()
    {
        if (!$this->is_active) return false;

        $now = Carbon::now();
        
        $started = $this->start_date ? $now->greaterThanOrEqualTo($this->start_date) : true;
        $ended = $this->end_date ? $now->greaterThan($this->end_date) : false;

        return $started && !$ended;
    }

    public function getStatusLabelAttribute()
    {
        if (!$this->is_active) return 'DISABLED';
        
        $now = Carbon::now();

        if ($this->end_date && $now->greaterThan($this->end_date)) {
            return 'EXPIRED';
        }

        if ($this->start_date && $now->lessThan($this->start_date)) {
            $days = $now->diffInDays($this->start_date, false);
            $days = abs(intval($days));
            
            if ($days == 0) return "STARTS TOMORROW";
            return "STARTS IN " . $days . " DAYS";
        }

        return 'LIVE NOW';
    }
}