<!-- resources/js/Components/DashboardHeader.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3';

const logout = () => {
    router.post('/logout');
};

const userNotifications = [
    { type: 'points', message: 'Anda dapat 50 poin!', time: '2 jam lalu' },
    { type: 'routine', message: 'Rutinitas pagi belum lengkap', time: '5 jam lalu' }
];
</script>

<template>
    <header class="bg-white border-b border-rose-200 shadow-sm">
        <div class="flex items-center justify-between px-6 py-4">
            <div>
                <slot name="title">
                    <h1 class="text-2xl font-light text-rose-800">Dashboard</h1>
                </slot>
                <slot name="subtitle">
                    <p class="text-rose-600/80 text-sm mt-1">Selamat datang di perjalanan kecantikan Anda</p>
                </slot>
            </div>
            
            <div class="flex items-center gap-4">
                <!-- Notification Bell with Badge -->
                <div class="relative">
                    <button class="flex items-center gap-2 px-4 py-2 text-rose-600 hover:text-rose-700 border border-rose-300 hover:border-rose-400 rounded-lg transition-all duration-200 hover:bg-rose-50">
                        <span class="text-lg">🔔</span>
                        <span class="hidden sm:inline">Notifikasi</span>
                    </button>
                    <span class="absolute -top-1 -right-1 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center">
                        {{ userNotifications.length }}
                    </span>
                </div>

                <!-- User Points -->
                <div class="flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                    <span class="text-lg">⭐</span>
                    <span class="text-amber-800 font-medium">1,250</span>
                </div>

                <!-- Logout Button -->
                <button
                    @click="logout"
                    class="flex items-center gap-2 px-4 py-2 text-rose-600 hover:text-rose-700 border border-rose-300 hover:border-rose-400 rounded-lg transition-all duration-200 hover:bg-rose-50"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    <span class="hidden sm:inline">Logout</span>
                </button>
            </div>
        </div>
    </header>
</template>