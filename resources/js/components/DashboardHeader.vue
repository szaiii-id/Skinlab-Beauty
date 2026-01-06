<script setup lang="ts">
import { onMounted } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { Home, LogOut, Sparkles } from 'lucide-vue-next';
import { requestPermission } from '@/firebase'; 

// IMPORT KOMPONEN NOTIFIKASI PINTAR
import NotificationDropdown from '@/components/NotificationDropdown.vue'; 

const logout = () => { router.post('/logout'); };

onMounted(() => {
    // Minta izin notifikasi browser
    requestPermission();
});
</script>

<template>
    <header class="sticky top-0 z-30 w-full bg-white/80 backdrop-blur-md border-b border-rose-100 shadow-sm transition-all duration-300 h-16 sm:h-20 shrink-0">
        
        <div class="w-full h-full px-4 sm:px-6 flex items-center justify-between">
            
            <div class="flex items-center gap-4 sm:gap-6">
                <Link href="/" class="flex flex-col items-start leading-none group/logo hover:opacity-80 transition-opacity">
                    <span class="text-lg sm:text-xl font-black text-rose-600 tracking-tight">SkinLab</span>
                    <span class="text-[0.55rem] sm:text-[0.65rem] font-bold text-rose-400 tracking-[0.3em] uppercase">Beauty</span>
                </Link>

                <div class="h-8 w-px bg-gray-200 hidden sm:block"></div>

                <Link href="/dashboard" class="flex flex-col justify-center group cursor-pointer">
                    <div class="flex items-center gap-2">
                        <div class="bg-rose-50 p-1.5 rounded-lg shadow-sm hidden sm:block group-hover:bg-rose-100 transition-colors">
                            <Sparkles class="w-4 h-4 text-rose-500" />
                        </div>
                    </div>
                    <p class="hidden sm:block text-[11px] text-gray-500 font-medium ml-0 sm:ml-8 mt-0.5">
                        Manage your beauty journey
                    </p>
                </Link>
            </div>
            
            <div class="flex items-center gap-2 sm:gap-3">
                
                <Link 
                    href="/" 
                    class="p-2 text-gray-500 hover:text-rose-600 hover:bg-rose-50 rounded-full transition-all"
                    title="Go to Homepage"
                >
                    <Home class="w-5 h-5 sm:w-5.5 sm:h-5.5" />
                </Link>

                <div class="h-6 w-px bg-gray-200 mx-1 hidden sm:block"></div>

                <div class="relative flex items-center">
                    <NotificationDropdown />
                </div>

                <button 
                    @click="logout" 
                    class="flex items-center gap-2 px-3 py-2 sm:px-4 text-rose-600 bg-white hover:bg-rose-600 hover:text-white border border-rose-100 hover:border-rose-600 rounded-full transition-all shadow-sm hover:shadow-md text-xs font-bold uppercase tracking-wide group ml-1"
                >
                    <span class="hidden sm:inline">Logout</span>
                    <LogOut class="w-4 h-4 sm:w-3.5 sm:h-3.5 transition-transform group-hover:translate-x-1" /> 
                </button>
            </div>

        </div>
    </header>
</template>