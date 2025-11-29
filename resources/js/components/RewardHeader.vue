<script setup>
import { Star } from 'lucide-vue-next';

defineProps({
    points: Number
});

const formatNumber = (num) => new Intl.NumberFormat('en-US').format(num);
</script>

<template>
    <div class="bg-gradient-to-r from-amber-400 to-orange-500 rounded-3xl p-8 text-white shadow-lg mb-8 relative overflow-hidden">
        <div class="relative z-10">
            <p class="text-amber-100 font-medium mb-1">Your Point Balance</p>
            <h1 class="text-5xl font-bold flex items-center gap-2">
                {{ formatNumber(points) }} <span class="text-2xl opacity-80">pts</span>
            </h1>
            <p class="text-sm mt-4 text-white/90 bg-white/20 inline-block px-3 py-1 rounded-full backdrop-blur-sm">
                Earn 1 point for every IDR 10,000 spent
            </p>
        </div>
        <Star class="absolute -right-6 -bottom-6 w-40 h-40 text-white opacity-20 rotate-12" />
        <div class="absolute top-0 right-0 w-32 h-32 bg-white opacity-10 rounded-full blur-2xl -mr-10 -mt-10"></div>
    </div>
</template>