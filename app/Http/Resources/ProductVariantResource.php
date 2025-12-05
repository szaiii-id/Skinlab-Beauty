<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariantResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'variant_id' => $this->id,
            'volume' => $this->volume,
            'color_shade' => $this->color_shade,
            'price' => $this->price,
            'stock' => $this->stock,
            'image_url' => $this->image,
            'final_price' => $this->final_price,
            'discount_info' => $this->discount_info,
        ];
    }
}
