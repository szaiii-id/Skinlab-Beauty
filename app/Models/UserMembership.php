<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserMembership extends Model
{
    protected $fillable = [
        'user_id',
        'tier',
        'total_spend',
        'last_upgraded_at'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}