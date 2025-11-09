<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductVariant>
 */
class ProductVariantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [

            'volume' => fake()->randomElement(['30ml', '50ml', '100ml']),
            'color_shade' => null, 

            'price' => fake()->numberBetween(100, 350) * 1000, 
            'stock' => fake()->numberBetween(10, 100),
            'sku' => fake()->unique()->ean8(),
        ];
    }
}
