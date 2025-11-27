<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'user_id',
        'reason',
        'description',
        'evidence_file', 
        'solution',
        'status',
        'admin_note'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
