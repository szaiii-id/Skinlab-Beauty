<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { useFormatting } from '@/composables/useFormatting';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';

defineOptions({
    layout: AppNavbarLayout // <-- 2. GANTI INI
});

const props = defineProps({
    cart: Object
});

const { formatCurrency } = useFormatting();

const cartTotal = computed(() => {
    let total = 0;
    for (const variantId in props.cart) {
        total += props.cart[variantId].price * props.cart[variantId].quantity;
    }
    return total;
});

const isCartEmpty = computed(() => {
    return Object.keys(props.cart).length === 0;
});
</script>

<template>
    <Head title="Shopping Cart" />

    <div class="bg-rose-50 min-h-screen py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-4xl font-light text-gray-900 tracking-tight">
                    Shopping Cart
                </h1>
            </div>

            <div class="bg-white rounded-lg shadow-xl overflow-hidden">
                
                <div v-if="isCartEmpty" class="p-8 text-center">
                    <p class="text-gray-600">Your cart is currently empty.</p>
                    <Link 
                        href="/" 
                        class="inline-block mt-6 text-sm text-rose-600 hover:underline font-medium"
                    >
                        &larr; Continue Shopping
                    </Link>
                </div>

                <div v-else>
                    <div class="divide-y divide-gray-200">
                        <div v-for="(item, id) in cart" :key="id" class="p-6 flex justify-between items-center">
                            <div>
                                <h2 class="text-lg font-medium text-gray-900">{{ item.name }}</h2>
                                <p class="text-sm text-gray-600">{{ formatCurrency(item.price) }} x {{ item.quantity }}</p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-semibold text-gray-900">
                                    {{ formatCurrency(item.price * item.quantity) }}
                                </p>
                                <Link 
                                    href="#" 
                                    as="button"
                                    class="text-xs text-red-600 hover:underline mt-1"
                                >
                                    Remove
                                </Link>
                            </div>
                        </div>
                    </div>

                    <div class="p-6 bg-gray-50 border-t border-gray-200">
                        <div class="flex justify-between items-center">
                            <span class="text-lg font-medium text-gray-900">Total</span>
                            <span class="text-2xl font-bold text-gray-900">
                                {{ formatCurrency(cartTotal) }}
                            </span>
                        </div>
                        <button 
                            class="w-full bg-rose-600 text-white p-3 rounded-md mt-6 font-semibold
                                   transition-colors duration-300
                                   hover:bg-rose-700"
                        >
                            Proceed to Checkout
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>