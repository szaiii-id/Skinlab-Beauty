<?php
// app/Models/BanRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BanRequest extends Model
{
    
    protected $fillable = [
        'user_id',
        'requested_by',
        'reason',
        'description',
        'evidence',
        'status',
        'reviewed_by',
        'reviewed_at',
        'review_notes'
    ];
    
    protected $casts = [
        'evidence' => 'array',
        'reviewed_at' => 'datetime'
    ];
    
    // ============ CONSTANTS ============
    
    // Status
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    
    // Reason
    const REASON_RETURN_ABUSE = 'return_abuse';
    const REASON_FRAUD = 'fraud';
    const REASON_TOXIC_BEHAVIOR = 'toxic_behavior';
    const REASON_PAYMENT_ISSUE = 'payment_issue';
    const REASON_POLICY_VIOLATION = 'policy_violation';
    const REASON_OTHER = 'other';
    
    // ============ RELATIONSHIPS ============
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function requester(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'requested_by');
    }
    
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'reviewed_by');
    }
    
    // ============ HELPER METHODS ============
    
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
    
    public function isApproved(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }
    
    public function isRejected(): bool
    {
        return $this->status === self::STATUS_REJECTED;
    }
    
    public function getStatusBadgeClass(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'bg-yellow-100 text-yellow-800',
            self::STATUS_APPROVED => 'bg-green-100 text-green-800',
            self::STATUS_REJECTED => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800'
        };
    }
    
    public function getReasonText(): string
    {
        return match($this->reason) {
            self::REASON_RETURN_ABUSE => 'Return Abuse',
            self::REASON_FRAUD => 'Fraud',
            self::REASON_TOXIC_BEHAVIOR => 'Toxic Behavior',
            self::REASON_PAYMENT_ISSUE => 'Payment Issue',
            self::REASON_POLICY_VIOLATION => 'Policy Violation',
            self::REASON_OTHER => 'Other',
            default => $this->reason
        };
    }
}