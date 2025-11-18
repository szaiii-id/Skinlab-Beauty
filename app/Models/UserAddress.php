<?php
// [file name]: app/Models/UserAddress.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserAddress extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'receiver_name',
        'phone_number',
        'province_code',
        'city_code',
        'district_code',
        'full_address',
        'postal_code',
        'latitude',      
        'longitude',     
        'type',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'latitude' => 'decimal:8',   
        'longitude' => 'decimal:8',  
    ];

    public function province(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\Province::class, 'province_code', 'code');
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\City::class, 'city_code', 'code');
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(\Laravolt\Indonesia\Models\District::class, 'district_code', 'code');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeNearby($query, $latitude, $longitude, $radiusKm = 10)
    {
        return $query->selectRaw(
            "*, (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance",
            [$latitude, $longitude, $latitude]
        )->having('distance', '<', $radiusKm)
         ->orderBy('distance');
    }

    public function getCoordinatesAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return [
                'lat' => (float) $this->latitude,
                'lng' => (float) $this->longitude
            ];
        }
        return null;
    }

    public function getMapUrlAttribute()
    {
        if ($this->latitude && $this->longitude) {
            return "https://www.openstreetmap.org/?mlat={$this->latitude}&mlon={$this->longitude}#map=15/{$this->latitude}/{$this->longitude}";
        }
        return null;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($address) {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });

        static::updating(function ($address) {
            if ($address->is_default) {
                static::where('user_id', $address->user_id)
                    ->where('id', '!=', $address->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }
        });
    }
}