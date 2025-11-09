<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserAdresses extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'receiver_name',
        'phone_number',
        'province',
        'city',
        'postal_code',
        'full_address',
        'is_default',
    ];

    /**
     * Get the user that owns the UserAdresses
     *
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
