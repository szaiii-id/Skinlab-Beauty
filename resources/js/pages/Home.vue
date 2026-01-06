<script setup>
import { computed, onMounted } from 'vue'; 
import { Head, Link, usePage } from '@inertiajs/vue3'; // Head & Link digabung importnya
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
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 md:py-10">
        
        <div class="mb-8 md:mb-12">
            <PromoCarousel v-if="promoBanners && promoBanners.length > 0" :slides="promoBanners" />
        </div>

        <div 
            v-if="recommendedProducts && recommendedProducts.length > 0" 
            class="mb-12 md:mb-16 bg-gradient-to-br from-rose-50 via-white to-rose-50/30 rounded-2xl md:rounded-3xl p-5 md:p-8 border border-rose-100 shadow-sm relative overflow-hidden"
        >
            <div class="absolute top-0 right-0 -mr-10 -mt-10 w-32 md:w-40 h-32 md:h-40 bg-rose-200 rounded-full blur-3xl opacity-30"></div>

            <div class="relative z-10">
                <div class="flex flex-col sm:flex-row items-start sm:items-center gap-3 mb-6">
                    <div class="p-2 bg-rose-100 rounded-full text-rose-600 shadow-sm">
                        <Sparkles class="w-5 h-5 md:w-6 md:h-6" />
                    </div>
                    <div>
                        <h2 class="text-xl md:text-2xl font-bold text-gray-900 leading-tight">
                            Picks for Your {{ userSkinType || 'Skin' }}
                        </h2>
                        <p class="text-gray-500 text-xs md:text-sm mt-1">
                            Curated based on your <Link href="/skin-analysis" class="text-rose-600 font-medium hover:underline decoration-rose-300 decoration-2 underline-offset-2">skin analysis</Link>.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
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
            class="mb-12 md:mb-16 bg-white rounded-2xl p-6 md:p-8 text-center border border-dashed border-gray-300 shadow-sm"
        >
            <div class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4">
                <ScanFace class="w-8 h-8 text-gray-400" />
            </div>
            <h3 class="text-lg font-semibold text-gray-900">Want personal recommendations?</h3>
            <p class="text-gray-500 mb-5 text-sm">Check your skin type in just 2 minutes.</p>
            <Link 
                href="/skin-analysis" 
                class="inline-flex items-center px-6 py-2.5 bg-gray-900 text-white rounded-full text-sm font-semibold hover:bg-rose-600 transition-colors shadow-lg shadow-gray-200"
            >
                Start Skin Analysis
            </Link>
        </div>

        <div class="text-center mb-10 md:mb-16 mt-4 md:mt-8 px-4">
            <h1 class="text-3xl md:text-5xl font-light text-gray-900 tracking-tight leading-tight">
                Curated for Your <span class="text-rose-600 font-normal">Glow</span>
            </h1>
            <p class="text-gray-500 mt-2 md:mt-4 text-sm md:text-lg max-w-2xl mx-auto">
                Discover the latest arrivals and our top-selling secrets tailored for radiant skin.
            </p>
        </div>

        <div class="mb-12 md:mb-16">
            <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-2">
                <h2 class="text-xl md:text-3xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">✨</span> New Releases
                </h2>
                <Link href="/search?q=new" class="text-xs md:text-sm font-semibold text-rose-600 hover:text-rose-700">View All</Link>
            </div>
            
            <div v-if="newReleases.length === 0" class="text-center py-8 bg-gray-50 rounded-xl">
                <p class="text-gray-500 text-sm">No new products available yet.</p>
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
                <ProductCard v-for="product in newReleases" :key="product.id" :product="product" />
            </div>
        </div>

        <div class="mb-16">
            <div class="flex items-center justify-between mb-6 border-b border-gray-100 pb-2">
                <h2 class="text-xl md:text-3xl font-bold text-gray-800 flex items-center gap-2">
                    <span class="text-xl">🔥</span> Best Sellers
                </h2>
                <Link href="/search?q=best" class="text-xs md:text-sm font-semibold text-rose-600 hover:text-rose-700">View All</Link>
            </div>

            <div v-if="bestSellers.length === 0" class="text-center py-8 bg-gray-50 rounded-xl">
                <p class="text-gray-500 text-sm">No best-selling products yet.</p>
            </div>
            <div v-else class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-3 md:gap-6">
                <ProductCard v-for="product in bestSellers" :key="product.id" :product="product" />
            </div>
        </div>
        
        <div class="text-center mt-8 md:mt-12 pb-8">
            <Link 
                href="/catalog" 
                class="inline-block px-8 py-3.5 bg-white text-gray-900 border border-gray-200 text-sm md:text-base font-bold rounded-full shadow-sm hover:shadow-md hover:border-rose-200 hover:text-rose-600 transition-all duration-300"
            >
                View Full Catalog
            </Link>
        </div>
    </div>
</template>