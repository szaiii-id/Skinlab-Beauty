<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SkincareRoutine extends Model
{
    protected $fillable = [
        'user_id',
        'product_id',
        'custom_product_name',
        'step_order',
        'period',
        'note',
        'reminder_time',
        'is_reminder_active',
        'repeat_frequency'
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function currentMonthCompletion(): HasOne
    {
        return $this->hasOne(RoutineCompletion::class)
            ->where('year_month', now()->format('Y-m'));
    }
}