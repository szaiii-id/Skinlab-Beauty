<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RoutineCompletion extends Model
{
    protected $fillable = [
        'user_id',
        'skincare_routine_id',
        'year_month',
        'completed_days'
    ];

    protected $casts = [
        'completed_days' => 'array',
    ];
}