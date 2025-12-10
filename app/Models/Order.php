<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable; // <--- WAJIB: Import ini untuk Elastic

class Order extends Model
{
    use HasFactory, Searchable; // <--- WAJIB: Pasang Trait ini

    protected $guarded = ['id'];

    // Casting agar tipe data konsisten (terutama Desimal/Uang)
    protected $casts = [
        'total_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ============ CONSTANTS (Agar kodingan Controller rapi & tidak Typo) ============
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    const PAYMENT_UNPAID = 'unpaid';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_EXPIRED = 'expired';
    const PAYMENT_FAILED = 'failed';

    // ============ ELASTICSEARCH CONFIGURATION ============
    
    /**
     * Tentukan nama index di Elasticsearch.
     */
    public function searchableAs(): string
    {
        return 'orders_index';
    }

    /**
     * Tentukan data apa saja yang dikirim ke Elasticsearch.
     * Kita "meratakan" (flatten) data user agar Admin bisa cari nama customer di tabel order.
     */
    public function toSearchableArray(): array
    {
        // Load relasi user agar namanya bisa diambil
        $this->loadMissing('user');

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'resi_number' => $this->resi_number ?? '',
            'order_status' => $this->order_status,
            'payment_status' => $this->payment_status,
            'total_amount' => (float) $this->total_amount,
            'created_at' => $this->created_at->timestamp,
            
            // Data Nested (User) dibuat flat agar searchable
            'user_name' => $this->user ? $this->user->name : '',
            'user_email' => $this->user ? $this->user->email : '',
        ];
    }

    // ============ RELASI DATABASE ============

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function shippingAddress()
    {
        // Menggunakan withTrashed() sangat penting!
        // Jika User menghapus alamatnya, histori order TIDAK BOLEH error.
        return $this->belongsTo(UserAddress::class, 'shipping_address_id')->withTrashed();
    }

    public function cancellation()
    {
        return $this->hasOne(OrderCancellation::class);
    }

    public function returns()
    {
        return $this->hasMany(OrderReturn::class);
    }

    public function returnRequest()
    {
        return $this->hasOne(OrderReturn::class);
    }

    // ============ SCOPES (Fallback jika Elastic mati/belum setup) ============
    
    public function scopeFilter($query, array $filters)
    {
        // Filter by Search (Order ID / Nama User)
        $query->when($filters['search'] ?? null, function ($query, $search) {
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', '%'.$search.'%')
                  ->orWhereHas('user', function($qUser) use ($search) {
                      $qUser->where('name', 'like', '%'.$search.'%');
                  });
            });
        });

        // Filter by Status
        $query->when($filters['status'] ?? null, function ($query, $status) {
            if ($status !== 'all') {
                $query->where('order_status', $status);
            }
        });

        // Filter by Date
        $query->when($filters['date'] ?? null, function ($query, $date) {
            $query->whereDate('created_at', $date);
        });
    }
}