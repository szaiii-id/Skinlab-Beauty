<script setup>
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';

defineProps({
    user: Object,
    points: Number
});
</script>

<template>
    <div class="relative bg-gradient-to-r from-rose-600 to-pink-500 rounded-3xl p-8 text-white shadow-xl overflow-hidden mb-8">
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
            <div>
                <p class="text-rose-100 font-medium mb-1">Welcome Back,</p>
                <h1 class="text-3xl md:text-4xl font-bold">{{ user.name }}! 👋</h1>
                <p class="mt-2 text-rose-100 text-sm max-w-md leading-relaxed">
                    Don't forget to check your skincare routine today to keep your skin glowing and healthy.
                </p>
            </div>
            
            <div class="bg-white/10 backdrop-blur-md border border-white/20 p-4 rounded-2xl flex items-center gap-4 min-w-[200px] shadow-lg">
                <div class="bg-amber-400 p-2.5 rounded-full shadow-inner">
                    <Star class="w-6 h-6 text-white fill-white" />
                </div>
                <div>
                    <p class="text-[10px] text-rose-100 uppercase tracking-wider font-bold">Beauty Points</p>
                    <p class="text-2xl font-bold">{{ points }}</p>
                </div>
                <Link href="/rewards" class="ml-auto bg-white text-rose-600 px-4 py-1.5 rounded-full text-xs font-bold hover:bg-rose-50 hover:shadow-md transition-all duration-200">
                    Redeem
                </Link>
            </div>
        </div>
        
        <div class="absolute -bottom-20 -right-20 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute -top-20 -left-20 w-40 h-40 bg-rose-400 opacity-30 rounded-full blur-2xl"></div>
    </div>
</template>