<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable; 
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Admin extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $table = 'admins';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'is_active' => 'boolean',
        'last_login_at' => 'datetime'
    ];


    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_WAREHOUSE = 'warehouse';
    const ROLE_MARKETING = 'marketing';
    
    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPER_ADMIN;
    }
    
    public function isWarehouse(): bool
    {
        return $this->role === self::ROLE_WAREHOUSE;
    }
    
    public function isMarketing(): bool
    {
        return $this->role === self::ROLE_MARKETING;
    }
    
    public function canApproveBans(): bool
    {
        return $this->isSuperAdmin();
    }
    
    public function canRequestBan(): bool
    {
        return $this->is_active;
    }
    
    // ============ RELATIONSHIPS ============
    
    public function banRequests()
    {
        return $this->hasMany(BanRequest::class, 'requested_by');
    }
    
    public function reviewedBanRequests()
    {
        return $this->hasMany(BanRequest::class, 'reviewed_by');
    }
    
    public function bannedUsers()
    {
        return $this->hasMany(User::class, 'banned_by');
    }

}