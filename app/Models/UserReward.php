<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class UserReward extends Model {
    
    protected $fillable = [
        'user_id',
        'reward_id',
        'code',
        'is_used',
        'expires_at'];
    
    public function reward() {
        return $this->belongsTo(Reward::class);
    }
}