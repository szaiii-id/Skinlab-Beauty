<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useCart } from '@/composables/useCart';
import { useFormatting } from '@/composables/useFormatting';

const props = defineProps({
    product: Object
});

const selectedVariantId = ref(props.product.variants[0]?.variant_id || '');
const quantity = ref(1);

const { addToCart, isAddingToCart } = useCart();
const { formatCurrency } = useFormatting();

const currentVariant = computed(() => {
    return props.product.variants.find(
        v => v.variant_id === selectedVariantId.value
    );
});

const handleAddToCartClick = () => {
    if (!selectedVariantId.value) {
        alert('Please select a variant first.');
        return;
    }
    addToCart(selectedVariantId.value, quantity.value);
};

</script>

<template>
    <Head :title="product.name" />

    <div class="bg-rose-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                <div class="p-8 md:flex">
                    
                    <div class="md:w-1/2">
                        <Link href="/" class="text-sm text-rose-600 hover:underline font-medium">&larr; Back to Catalog</Link>

                        <span class="block text-sm text-rose-500 uppercase font-semibold tracking-wider mt-4">
                            {{ product.category.name }}
                        </span>

                        <h1 class="text-4xl font-light text-gray-900 mt-2">{{ product.name }}</h1>
                        
                        <p class="text-gray-600 mt-6">{{ product.description }}</p>
                    </div>

                    <div class="md:ml-10 md:w-1/2 mt-6 md:mt-0">
                        
                        <div class="mt-6">
                            <label for="variant-select" class="block text-sm font-medium text-gray-700">Select Variant:</label>
                            <select 
                                v-model="selectedVariantId" 
                                id="variant-select" 
                                class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                       focus:outline-none focus:ring-rose-500 focus:border-rose-500"
                            >
                                <option disabled value="">-- Select Size --</option>
                                <option 
                                    v-for="variant in product.variants" 
                                    :key="variant.variant_id"
                                    :value="variant.variant_id"
                                    :disabled="variant.stock === 0"
                                >
                                    {{ variant.volume }} {{ variant.color_shade || '' }} 
                                    ({{ formatCurrency(variant.price) }})
                                    <span v-if="variant.stock === 0"> - Out of Stock</span>
                                </option>
                            </select>
                        </div>

                        <div v-if="currentVariant" class="mt-4">
                            <p class="text-3xl font-light text-gray-900">
                                {{ formatCurrency(currentVariant.price) }}
                            </p>
                            <p :class="currentVariant.stock > 0 ? 'text-green-600' : 'text-red-600'" class="text-sm font-medium mt-1">
                                Stock: {{ currentVariant.stock }}
                            </p>
                        </div>
                        
                        <div class="mt-4">
                            <label for="quantity" class="block text-sm font-medium text-gray-700">Quantity:</label>
                            <input 
                                type="number" 
                                id="quantity" 
                                v-model="quantity" 
                                min="1" 
                                :max="currentVariant ? currentVariant.stock : 1"
                                class="mt-1 block w-20 py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm
                                       focus:outline-none focus:ring-rose-500 focus:border-rose-500"
                            >
                        </div>

                        <button 
                            @click="handleAddToCartClick"
                            :disabled="!currentVariant || currentVariant.stock === 0 || isAddingToCart"
                            class="w-full bg-rose-600 text-white p-3 rounded-md mt-6 font-semibold
                                   transition-colors duration-300
                                   hover:bg-rose-700 disabled:bg-rose-300 disabled:cursor-not-allowed"
                        >
                            {{ isAddingToCart ? 'Processing...' : (currentVariant && currentVariant.stock > 0 ? 'Add to Cart' : 'Out of Stock') }}
                        </button>

                    </div>
                </div>
            </div>
        </div>
    </div>
</template>