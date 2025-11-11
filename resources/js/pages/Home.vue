<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue'; // Pastikan path benar
import PromoCarousel from '@/components/PromoCarousel.vue';
import ProductCard from '@/components/ProductCard.vue';
import { useFormatting } from '@/composables/useFormatting';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    newReleases: Array, 
    bestSellers: Array,
    promoBanners: Array, // Data dari HomeController
});

const { formatCurrency } = useFormatting();
</script>

<template>
    <Head title="Home" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-12">
            <PromoCarousel v-if="promoBanners && promoBanners.length > 0" :slides="promoBanners" />
        </div>

        <div class="text-center mb-16 mt-8">
            <h1 class="text-4xl font-light text-gray-900 tracking-tight">
                Curated for Your Glow
            </h1>
            <p class="text-gray-500 mt-3 text-lg">
                Discover the latest arrivals and our top-selling secrets.
            </p>
        </div>


        <div class="mb-16">
            <h2 class="text-3xl font-medium text-gray-800 mb-6 border-b pb-2">✨ New Releases</h2>
            <div v-if="newReleases.length === 0" class="text-center py-4">
                <p class="text-gray-600">No new products available yet.</p>
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <ProductCard v-for="product in newReleases" :key="product.id" :product="product" />
            </div>
        </div>

        <div class="mb-16">
            <h2 class="text-3xl font-medium text-gray-800 mb-6 border-b pb-2">🔥 Best Sellers</h2>
            <div v-if="bestSellers.length === 0" class="text-center py-4">
                <p class="text-gray-600">No best-selling products yet.</p>
            </div>
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                <ProductCard v-for="product in bestSellers" :key="product.id" :product="product" />
            </div>
        </div>
        
        <div class="text-center mt-12">
            <Link 
                href="/catalog" 
                class="inline-block px-8 py-3 bg-rose-600 text-white text-lg font-semibold rounded-full shadow-lg 
                    hover:bg-rose-700 transition-colors duration-300"
            >
                View All Products &rarr;
            </Link>
        </div>
    </div>
</template>