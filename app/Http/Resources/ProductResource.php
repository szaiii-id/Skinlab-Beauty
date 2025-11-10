<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\CategoryResource; // <-- Pastikan ini di-import
use App\Http\Resources\ProductVariantResource; // <-- Pastikan ini di-import

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'category' => new CategoryResource($this->whenLoaded('category')),
            
            // INI YANG DIPERBAIKI:
            // SALAH: 'variant' => ProductVariantResource::collection($this->whenLoaded('variants')),
            // BENAR: (plural 'variants')
            'variants' => ProductVariantResource::collection($this->whenLoaded('variants')),
        ];
    }
}