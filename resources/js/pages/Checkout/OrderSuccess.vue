<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import AppNavbarLayout from '@/layouts/app/AppNavbarLayout.vue';
import { ref, onMounted, onUnmounted } from 'vue';

defineOptions({
    layout: AppNavbarLayout
});

// --- AUTO REDIRECT LOGIC ---
const countdown = ref(5); // 5 Seconds delay
let intervalId = null;

const redirectToDashboard = () => {
    router.visit('/dashboard');
};

onMounted(() => {
    // Start countdown
    intervalId = setInterval(() => {
        if (countdown.value > 0) {
            countdown.value--;
        } else {
            // Stop timer and redirect
            clearInterval(intervalId);
            redirectToDashboard();
        }
    }, 1000);
});

// Cleanup timer if user clicks a link manually before time runs out
onUnmounted(() => {
    if (intervalId) clearInterval(intervalId);
});
</script>

<template>
    <Head title="Order Successful - SkinLab Beauty" />

    <div class="min-h-screen bg-rose-50 flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8 font-sans">
        <div class="max-w-md w-full bg-white p-10 rounded-2xl shadow-xl border border-rose-100 text-center transform transition-all relative overflow-hidden">
            
            <div class="mx-auto flex items-center justify-center h-24 w-24 rounded-full bg-green-100 mb-6 animate-bounce-slow">
                <svg class="h-12 w-12 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>

            <h2 class="text-3xl font-bold text-gray-900 mb-2">
                Order Successful!
            </h2>
            
            <p class="text-gray-600 mb-6 leading-relaxed">
                Thank you for shopping at <span class="font-semibold text-rose-600">SkinLab Beauty</span>. 
                <br>
                Your order is being processed. Please check your dashboard for payment and shipping status.
            </p>

            <p class="text-sm text-gray-400 mb-8 italic">
                Redirecting to dashboard in {{ countdown }}s...
            </p>

            <div class="space-y-4">
                <button 
                    @click="redirectToDashboard"
                    class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border border-transparent text-base font-bold rounded-xl text-white bg-rose-600 hover:bg-rose-700 transition-all shadow-lg shadow-rose-200 hover:shadow-rose-300"
                >
                    View My Orders Now
                </button>
                
                <Link href="/catalog" 
                    class="w-full flex justify-center items-center gap-2 py-3.5 px-4 border-2 border-gray-200 text-base font-bold rounded-xl text-gray-700 bg-white hover:bg-gray-50 hover:text-rose-600 hover:border-rose-200 transition-all"
                >
                    Continue Shopping
                </Link>
            </div>
        </div>
    </div>
</template>

<style scoped>
.animate-bounce-slow {
    animation: bounce 2s infinite;
}
@keyframes bounce {
    0%, 100% { transform: translateY(-5%); animation-timing-function: cubic-bezier(0.8, 0, 1, 1); }
    50% { transform: translateY(0); animation-timing-function: cubic-bezier(0, 0, 0.2, 1); }
}
</style>