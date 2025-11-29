<script setup>
import { Sparkles, RefreshCw, ShoppingBag, ScanFace } from 'lucide-vue-next';
import ProductCard from '@/components/ProductCard.vue';

defineProps({
    profile: Object,
    products: Array
});

const emit = defineEmits(['retake']);
</script>

<template>
    <div class="space-y-8 animate-fade-in">
        <div class="bg-white rounded-2xl shadow border border-gray-100 overflow-hidden relative">
            <div class="bg-gradient-to-r from-rose-500 to-pink-600 p-8 md:p-10 text-white">
                
                <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div>
                        <div class="flex items-center gap-2 text-rose-100 mb-2">
                            <Sparkles class="w-5 h-5" />
                            <span class="uppercase tracking-wider text-sm font-bold">Analysis Result</span>
                        </div>
                        <h2 class="text-4xl md:text-5xl font-bold mb-4">{{ profile?.skin_type }}</h2>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="c in profile?.skin_concerns" :key="c" class="bg-white/20 backdrop-blur-sm px-4 py-1.5 rounded-full text-sm font-medium border border-white/30">
                                {{ c }}
                            </span>
                        </div>
                    </div>

                    <button 
                        @click="$emit('retake')" 
                        class="flex items-center gap-2 px-5 py-2.5 bg-white/20 hover:bg-white/30 backdrop-blur-md border border-white/40 rounded-xl text-sm font-bold transition-all text-white shadow-lg hover:scale-105"
                    >
                        <RefreshCw class="w-4 h-4" /> Retake Analysis
                    </button>
                </div>

                <div class="absolute top-0 right-0 -mr-16 -mt-16 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
                <div class="absolute bottom-0 left-0 w-32 h-32 bg-rose-900 opacity-20 rounded-full blur-2xl"></div>
            </div>
        </div>

        <div class="space-y-4">
            <h3 class="text-2xl font-light text-gray-900 flex items-center gap-2">
                <ShoppingBag class="text-rose-600 w-6 h-6" /> Personalized Recommendations
            </h3>
            
            <div v-if="products && products.length > 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <ProductCard v-for="product in products" :key="product.id" :product="product">
                    <template #badge>
                        <div class="absolute top-3 right-3 bg-rose-500/90 backdrop-blur text-white text-[10px] font-bold px-2 py-1 rounded-full uppercase tracking-wider shadow-sm z-10">Best Match</div>
                    </template>
                </ProductCard>
            </div>

            <div v-else class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                <div class="text-gray-300 mb-4 mx-auto w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center">
                    <ScanFace class="w-10 h-10" />
                </div>
                <p class="text-gray-500 mb-6">No specific products found for your combination. Try updating your concerns.</p>
            </div>
        </div>
    </div>
</template>