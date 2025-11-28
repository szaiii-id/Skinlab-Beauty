<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $productName = fake()->words(3, true);

        $possibleTags = [
            'Oily Skin', 'Dry Skin', 'Combination Skin', 'Normal Skin', 
            'Acne', 'Aging', 'Dullness', 'Sensitive', 'Pores', 'Blackheads'
        ];

        return [
            'category_id' => Category::inRandomOrder()->first()->id,
            'brand_id' => Brand::inRandomOrder()->first()->id,
            'name' => 'Product ' . $productName,
            'slug' => Str::slug($productName),
            'description' => fake()->paragraph(),
            'suitability_tags' => fake()->randomElements($possibleTags, fake()->numberBetween(1, 4)),
        ];
    }
}
