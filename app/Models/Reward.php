<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 
        'description', 
        'image',
        'points_required',
        'type', // 'fixed', 'percent'
        'value', 
        'min_spend',
        'stock', 
        'max_per_user',
        'validity_days',
        'is_claim_only',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_claim_only' => 'boolean'
    ];
}