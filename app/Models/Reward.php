<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model {

    
    protected $fillable = [
        'name',
        'description',
        'points_required',
        'type',
        'value',
        'min_spend',
        'stock',
        'is_active'
    ];
}