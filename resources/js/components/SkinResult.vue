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
    <div class="animate-fade-in">

        <div class="hidden md:block space-y-8">
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
                    <ProductCard 
                        v-for="product in products" 
                        :key="product.id" 
                        :product="product" 
                    />
                </div>

                <div v-else class="bg-white rounded-2xl shadow border border-gray-100 p-12 text-center">
                    <div class="text-gray-300 mb-4 mx-auto w-20 h-20 bg-gray-50 rounded-full flex items-center justify-center">
                        <ScanFace class="w-10 h-10" />
                    </div>
                    <p class="text-gray-500 mb-6">No specific products found for your combination. Try updating your concerns.</p>
                </div>
            </div>
        </div>


        <div class="md:hidden space-y-6">
            
            <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-[2rem] p-6 text-white shadow-lg relative overflow-hidden ring-1 ring-black/5">
                <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10"></div>
                
                <div class="relative z-10 flex flex-col items-center text-center">
                    <div class="inline-flex items-center gap-1.5 bg-white/20 backdrop-blur-md px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-widest mb-4 border border-white/20 shadow-sm">
                        <Sparkles class="w-3 h-3" /> Result
                    </div>

                    <h2 class="text-3xl font-black mb-4 tracking-tight leading-none">{{ profile?.skin_type }}</h2>

                    <div class="flex flex-wrap justify-center gap-2 mb-6">
                        <span v-for="c in profile?.skin_concerns" :key="c" class="bg-black/10 backdrop-blur-sm px-3 py-1 rounded-lg text-[10px] font-bold border border-white/10">
                            {{ c }}
                        </span>
                    </div>

                    <button 
                        @click="$emit('retake')" 
                        class="w-full py-3 bg-white text-rose-600 rounded-xl text-xs font-bold shadow-sm active:scale-95 transition-transform flex items-center justify-center gap-2"
                    >
                        <RefreshCw class="w-3.5 h-3.5" /> Retake Analysis
                    </button>
                </div>
            </div>

            <div class="space-y-4">
                <h3 class="font-bold text-gray-900 text-lg flex items-center gap-2 px-1">
                    <ShoppingBag class="w-5 h-5 text-rose-500" />
                    Recommendations
                </h3>
                
                <div v-if="products && products.length > 0" class="grid grid-cols-2 gap-3">
                    <ProductCard 
                        v-for="product in products" 
                        :key="product.id" 
                        :product="product" 
                    />
                </div>

                <div v-else class="bg-white rounded-2xl border border-gray-100 p-8 text-center shadow-sm">
                    <ScanFace class="w-12 h-12 text-gray-300 mx-auto mb-3" />
                    <p class="text-xs font-medium text-gray-500">No products found. Try updating your concerns.</p>
                </div>
            </div>
        </div>

    </div>
</template>