<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'brand_id' => $this->brand_id,
            'category_id' => $this->category_id,
            'thumbnail' => $this->thumbnail, 
            'suitability_tags' => $this->suitability_tags ?? [],
            
           
            'variants' => $this->variants->map(function ($variant) {
                return [
                    'id' => $variant->id,
                    'volume' => $variant->volume,
                    'price' => (float) $variant->price, 
                    'stock' => (int) $variant->stock,  
                    'sku' => $variant->sku,
                    'image_url' => $variant->image_url, 
                ];
            }),
        ];
    }
}