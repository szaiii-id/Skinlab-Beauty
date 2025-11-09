<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory(20)->create()->each(function ($product){
            
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'volume' => '50ml',
                'price' => fake()->numberBetween(100, 150) * 1000,
                'stock' => fake()->numberBetween(10, 50),
                'sku' => 'SKN-' . $product->id . '-50', 
            ]);

            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'volume' => '100ml',
                'price' => fake()->numberBetween(160, 250) * 1000,
                'stock' => fake()->numberBetween(5, 30),
                'sku' => 'SKN-' . $product->id . '-100', 
            ]);
        });
    }
}
