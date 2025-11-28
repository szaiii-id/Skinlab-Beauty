<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import PromoCarousel from '@/components/PromoCarousel.vue';
import ProductCard from '@/components/ProductCard.vue';
import { Sparkles, ScanFace } from 'lucide-vue-next';

defineOptions({ layout: AppNavbarLayout });

const props = defineProps({
    newReleases: Array, 
    bestSellers: Array,
    promoBanners: Array,
    recommendedProducts: Array,
    userSkinType: String
});
</script>

<template>
    <Head title="Home" />
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-12">
            <PromoCarousel v-if="promoBanners && promoBanners.length > 0" :slides="promoBanners" />
        </div>

        <div 
            v-if="recommendedProducts && recommendedProducts.length > 0" 
            class="mb-16 bg-gradient-to-r from-rose-50 to-white rounded-3xl p-8 border border-rose-100 shadow-sm relative overflow-hidden"
        >
            <div class="absolute top-0 right-0 -mr-10 -mt-10 w-40 h-40 bg-rose-200 rounded-full blur-3xl opacity-30"></div>

            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-6">
                    <div class="p-2 bg-rose-100 rounded-full text-rose-600">
                        <Sparkles class="w-6 h-6" />
                    </div>
                    <div>
                        <h2 class="text-2xl font-semibold text-gray-900">
                            Picks for Your {{ userSkinType || 'Skin' }}
                        </h2>
                        <p class="text-gray-500 text-sm">
                            Curated products based on your <Link href="/skin-analysis" class="text-rose-600 hover:underline">skin analysis</Link>.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
                    <ProductCard 
                        v-for="product in recommendedProducts" 
                        :key="product.id" 
                        :product="product" 
                    />
                </div>
            </div>
        </div>

        <div 
            v-else-if="$page.props.auth.user && !recommendedProducts.length" 
            class="mb-16 bg-gray-50 rounded-2xl p-8 text-center border border-dashed border-gray-300"
        >
            <ScanFace class="w-10 h-10 text-gray-400 mx-auto mb-3" />
            <h3 class="text-lg font-medium text-gray-900">Want personal recommendations?</h3>
            <p class="text-gray-500 mb-4">Check your skin type in just 2 minutes.</p>
            <Link 
                href="/skin-analysis" 
                class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50 shadow-sm"
            >
                Start Skin Analysis
            </Link>
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