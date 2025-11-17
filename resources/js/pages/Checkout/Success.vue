<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';

defineOptions({
    layout: AppNavbarLayout
});
</script>

<template>
    <Head title="Order Successful - SkinLab Beauty" />

    <div class="min-h-screen bg-rose-50 py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-12">
                <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                
                <h1 class="text-3xl font-light text-gray-900 mb-4">Order Successful!</h1>
                <p class="text-gray-600 mb-2">Thank you for your purchase</p>
                <p class="text-gray-500 text-sm mb-8">Your order has been received and is being processed</p>

                <div class="space-y-4">
                    <Link 
                        href="/orders"
                        class="inline-flex items-center px-6 py-3 bg-rose-600 text-white font-semibold rounded-lg hover:bg-rose-700 transition-colors"
                    >
                        View My Orders
                    </Link>
                    
                    <Link 
                        href="/catalog"
                        class="inline-flex items-center px-6 py-3 border border-rose-600 text-rose-600 font-semibold rounded-lg hover:bg-rose-50 transition-colors ml-4"
                    >
                        Continue Shopping
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>