<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = usePage().props.auth.user;

// Logic sapaan berdasarkan waktu
const greeting = computed(() => {
    const hours = new Date().getHours();
    if (hours < 12) return 'Good Morning';
    if (hours < 18) return 'Good Afternoon';
    return 'Good Evening';
});
</script>

<template>
    <header class="bg-white/80 backdrop-blur-md border-b border-gray-200 h-20 flex items-center justify-between px-8 sticky top-0 z-20">
        
        <div>
            <h2 class="text-xl font-bold text-gray-800 tracking-tight">
                {{ greeting }}, {{ user.name.split(' ')[0] }}!
            </h2>
            <p class="text-xs text-gray-500 font-medium mt-0.5">
                Here is what's happening with your store today.
            </p>
        </div>
        
        <div class="flex items-center gap-6">
            
            <a href="/" target="_blank" class="hidden md:flex items-center text-xs font-semibold text-gray-500 hover:text-pink-600 transition-colors">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                Visit Store
            </a>

            <div class="h-8 w-px bg-gray-200 hidden md:block"></div>

            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800">{{ user.name }}</p>
                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider text-pink-700 bg-pink-100 rounded-full">
                        {{ user.role }}
                    </span>
                </div>
                <div class="w-10 h-10 rounded-full bg-gradient-to-tr from-pink-500 to-rose-600 flex items-center justify-center text-white font-bold text-sm shadow-md shadow-pink-200 ring-2 ring-white">
                    {{ user.name.charAt(0) }}
                </div>
            </div>
        </div>
    </header>
</template>