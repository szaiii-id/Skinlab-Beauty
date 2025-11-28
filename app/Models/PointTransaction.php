<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PointTransaction extends Model {

    protected $fillable = [
        'user_id', 
        'amount', 
        'source_type', 
        'description'
    ];
    
    // Agar tanggal tampil cantik
    protected $casts = [
        'created_at' => 'datetime'
    ];
}