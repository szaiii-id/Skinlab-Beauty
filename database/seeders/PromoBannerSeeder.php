<?php

namespace Database\Seeders;

use App\Models\PromoBanner;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PromoBannerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PromoBanner::create([
            'title' => 'Summer Sale',
            'subtitle' => 'Up to 50% Off on all skincare!',
            'image_url' => '/images/promo/promo-banner-1.jpg', // Gambar di folder public
            'link_url' => '/categories/skincare',
            'is_active' => true,
        ]);

        PromoBanner::create([
            'title' => 'New Arrivals',
            'subtitle' => 'Fresh products, fresh glow!',
            'image_url' => '/images/promo/promo-banner-2.jpg',
            'link_url' => '/products/new-arrivals',
            'is_active' => true,
        ]);

        PromoBanner::create([
            'title' => 'Free Shipping',
            'subtitle' => 'On all orders above $50. Shop now!',
            'image_url' => '/images/promo/promo-banner-3.jpg',
            'link_url' => '/',
            'is_active' => true, // Contoh promo non-aktif
        ]);
    }
}
