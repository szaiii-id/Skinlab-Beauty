<script setup>
import { Link } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';

// Terima prop produk tunggal dari halaman induk (Home.vue atau Catalog/Index.vue)
const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const { formatCurrency } = useFormatting();

// Dapatkan harga varian terendah untuk ditampilkan di card
const displayPrice = props.product.variants.length > 0 
    ? props.product.variants[0].price 
    : 0;

const displayCategory = props.product.category?.name || 'UNCATEGORIZED';
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden group transition-shadow duration-300 hover:shadow-xl">
        
        <div class="w-full h-64 bg-gray-100 flex items-center justify-center">
            <span class="text-gray-400 text-sm">Product Image</span>
        </div>
        
        <div class="p-5 text-center">
            <span class="text-xs text-rose-600 uppercase font-semibold tracking-wider">
                {{ product.brand?.name || displayCategory }}
            </span>

            <h2 class="text-lg font-medium text-gray-900 mt-2 truncate">
                {{ product.name }}
            </h2>

            <div class="mt-2">
                <p class="text-base font-bold text-gray-900">
                    {{ formatCurrency(displayPrice) }}
                </p>
            </div>

            <Link 
                :href="`/products/${product.slug}/${product.id}`" 
                class="inline-block w-full text-center border border-rose-600 text-rose-600 py-2 px-4 rounded-full mt-4
                       text-sm font-semibold transition-colors duration-300
                       hover:bg-rose-600 hover:text-white"
            >
                View Details
            </Link>
        </div>
    </div>
</template>