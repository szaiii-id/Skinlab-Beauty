<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserReward extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 
        'reward_id', 
        'code',
        'source', // 'redeem' / 'gift'
        'type', 'value', 'min_spend', // Snapshot Data
        'is_used', 
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean'
    ];

    public function reward()
    {
        return $this->belongsTo(Reward::class);
    }
}