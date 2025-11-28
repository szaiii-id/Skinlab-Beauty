<?php 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class UserSkinProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'skin_type',
        'skin_concerns',
        'answers_data'
    ];

    protected $casts = [
        'skin_concerns' => 'array',
        'answers_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}