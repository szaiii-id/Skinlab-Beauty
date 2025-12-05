<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Category extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    /** 
     * 
     * @var list<string>
    */
    protected $fillable = [
        'name',
        'slug',
    ];

    public function toSearchableArray()
    {
        return [
            'id' => (int) $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * Get all of the products for the Category
     *
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }


}
