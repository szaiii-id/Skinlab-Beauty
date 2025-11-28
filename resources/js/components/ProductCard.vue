<script setup>
import { Link } from '@inertiajs/vue3';
// Pastikan file composable ini ada, jika tidak, gunakan Intl.NumberFormat biasa
import { useFormatting } from '@/composables/useFormatting'; 
import { computed } from 'vue';

const props = defineProps({
    product: {
        type: Object,
        required: true
    }
});

const { formatCurrency } = useFormatting();

// Logic untuk mendapatkan harga dan gambar dari varian pertama (jika ada)
const displayPrice = computed(() => {
    return props.product.variants && props.product.variants.length > 0 
        ? props.product.variants[0].price 
        : (props.product.price || 0);
});

const displayImage = computed(() => {
    if (props.product.variants && props.product.variants.length > 0 && props.product.variants[0].image_url) {
        return props.product.variants[0].image_url;
    }
    return props.product.image_url || null; // Fallback ke image utama atau null
});

const displayCategory = computed(() => props.product.category?.name || 'UNCATEGORIZED');
</script>

<template>
    <div class="bg-white rounded-lg shadow-sm overflow-hidden group transition-all duration-300 hover:shadow-xl border border-gray-100 h-full flex flex-col">
        
        <div class="w-full aspect-square bg-gray-50 flex items-center justify-center overflow-hidden relative">
            <img 
                v-if="displayImage" 
                :src="displayImage" 
                :alt="product.name"
                class="w-full h-full object-cover object-center group-hover:scale-105 transition-transform duration-500"
            />
            <div v-else class="text-gray-400 text-sm flex flex-col items-center">
                <span>No Image</span>
            </div>

            <slot name="badge"></slot>
        </div>
        
        <div class="p-5 text-center flex-1 flex flex-col">
            <span class="text-xs text-rose-600 uppercase font-semibold tracking-wider mb-1">
                {{ product.brand?.name || displayCategory }}
            </span>

            <h2 class="text-lg font-medium text-gray-900 line-clamp-2 min-h-[3.5rem]">
                {{ product.name }}
            </h2>

            <div class="mt-2 mb-4">
                <p class="text-base font-bold text-gray-900">
                    {{ formatCurrency(displayPrice) }}
                </p>
            </div>

            <div class="mt-auto">
                <Link 
                    :href="`/products/${product.slug}/${product.id}`" 
                    class="block w-full text-center border border-rose-600 text-rose-600 py-2 px-4 rounded-full 
                           text-sm font-semibold transition-colors duration-300
                           hover:bg-rose-600 hover:text-white"
                >
                    View Details
                </Link>
            </div>
        </div>
    </div>
</template>