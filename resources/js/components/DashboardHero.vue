<script setup>
import { Link } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';

defineProps({
    user: Object,
    points: Number
});
</script>

<template>
    <div class="relative bg-gradient-to-br from-rose-600 to-pink-500 rounded-2xl md:rounded-3xl p-5 md:p-8 text-white shadow-xl shadow-rose-200/50 overflow-hidden mb-6 md:mb-8 transition-all duration-300">
        
        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-5 md:gap-6">
            
            <div class="w-full md:w-auto">
                <p class="text-rose-100 font-medium mb-1 text-sm md:text-base">Welcome Back,</p>
                <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold tracking-tight leading-tight">
                    {{ user.name }}! 👋
                </h1>
                <p class="mt-2 text-rose-100 text-xs sm:text-sm max-w-md leading-relaxed opacity-95 font-light">
                    Don't forget to check your skincare routine today to keep your skin glowing.
                </p>
            </div>
            
            <div class="w-full md:w-auto bg-white/10 backdrop-blur-md border border-white/20 p-3 sm:p-4 rounded-xl md:rounded-2xl flex items-center justify-between gap-4 shadow-lg ring-1 ring-white/10 group hover:bg-white/15 transition-colors">
                
                <div class="flex items-center gap-3">
                    <div class="bg-gradient-to-br from-amber-300 to-amber-500 p-2 sm:p-2.5 rounded-full shadow-lg shrink-0">
                        <Star class="w-5 h-5 sm:w-6 sm:h-6 text-white fill-white" />
                    </div>
                    
                    <div class="flex flex-col">
                        <p class="text-[9px] sm:text-[10px] text-rose-100 uppercase tracking-widest font-bold">Beauty Points</p>
                        <p class="text-xl sm:text-2xl font-bold leading-none mt-0.5">{{ points }}</p>
                    </div>
                </div>

                <Link 
                    href="/rewards" 
                    class="bg-white text-rose-600 px-4 py-2 rounded-full text-[10px] sm:text-xs font-bold hover:bg-rose-50 hover:shadow-md active:scale-95 transition-all duration-200 whitespace-nowrap"
                >
                    Redeem
                </Link>
            </div>

        </div>
        
        <div class="absolute -bottom-24 -right-24 w-48 md:w-64 h-48 md:h-64 bg-white opacity-10 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -top-24 -left-24 w-32 md:w-40 h-32 md:h-40 bg-rose-400 opacity-30 rounded-full blur-2xl pointer-events-none"></div>
    </div>
</template>