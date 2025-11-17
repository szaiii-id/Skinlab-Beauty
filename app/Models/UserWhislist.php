<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWhislist extends Model
{
    use HasFactory;

    protected $table = 'user_whislist';

    protected $fillable = [
        'user_id',
        'product_variant_id',
    ];

    protected $with = [
        'productVariant'
    ];

    /**
     * Get the user that owns the UserWhislist
     *
     * @return \Illu
     * minate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function productVariant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
