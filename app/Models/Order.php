<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi ke User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi ke Item Order
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    // ✅ UPDATE: Relasi ke UserAddress (Bukan Address biasa)
    public function address()
    {
        // Pastikan Anda punya model UserAddress.php
        return $this->belongsTo(UserAddress::class, 'shipping_address_id');
    }
}