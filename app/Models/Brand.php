<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable; // <--- WAJIB: Tambahkan ini

class Brand extends Model
{
    use HasFactory, Searchable; // <--- WAJIB: Pasang Trait ini

    protected $fillable = [
        'name',
        'slug',
    ];

    /**
     * Konfigurasi data untuk Elasticsearch
     */
    public function toSearchableArray()
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
        ];
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}