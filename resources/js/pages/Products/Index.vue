<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
    products: Array 
});

const { formatCurrency } = useFormatting();
</script>

<template>
    <Head title="Product Catalog" />

    <div class="bg-rose-50 min-h-screen">
        <div class="max-w-7xl mx-auto py-12 sm:px-6 lg:px-8">
            
            <div class="text-center mb-16">
                <h1 class="text-4xl font-light text-gray-900 tracking-tight">
                    Our Product Collection
                </h1>
                <p class="text-gray-500 mt-3 text-lg">
                    Discover your beauty with Skin Lab.
                </p>
            </div>

            <div v-if="!products || products.length === 0" class="text-center">
                <p class="text-gray-600">No products to display yet.</p>
            </div>

            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-8">
                
                <div 
                    v-for="product in products" 
                    :key="product.id" 
                    class="bg-white rounded-lg shadow-sm overflow-hidden group transition-shadow duration-300 hover:shadow-xl"
                >
                    <div class="w-full h-64 bg-gray-100 flex items-center justify-center">
                        <span class="text-gray-400 text-sm">Product Image</span>
                    </div>
                    
                    <div class="p-5 text-center">
                        <span class="text-xs text-rose-600 uppercase font-semibold tracking-wider">
                            {{ product.category.name }}
                        </span>

                        <h2 class="text-lg font-medium text-gray-900 mt-2 truncate">
                            {{ product.name }}
                        </h2>

                        <div v-if="product.variants.length > 0" class="mt-2">
                            <p class="text-base text-gray-700">
                                {{ formatCurrency(product.variants[0].price) }}
                            </p>
                        </div>

                        <Link 
                            :href="`/products/${product.id}`" 
                            class="inline-block w-full text-center border border-rose-600 text-rose-600 py-2 px-4 rounded-full mt-4
                                   text-sm font-semibold transition-colors duration-300
                                   hover:bg-rose-600 hover:text-white"
                        >
                            View Details
                        </Link>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>